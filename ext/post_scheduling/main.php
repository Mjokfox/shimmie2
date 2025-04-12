<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, TD, TH, TR};

final class PostScheduling extends DataHandlerExtension
{
    public const KEY = "post_scheduling";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {

            Ctx::$database->create_table("scheduled_images", "
                id SCORE_AIPK,
                owner_id INTEGER NOT NULL,
                owner_ip SCORE_INET NOT NULL,
                filename VARCHAR(255) NOT NULL,
                filesize INTEGER NOT NULL,
                hash CHAR(32) UNIQUE NOT NULL,
                ext CHAR(4) NOT NULL,
                source VARCHAR(255),
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                posted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                locked BOOLEAN NOT NULL DEFAULT FALSE,
                lossless BOOLEAN NULL,
                video BOOLEAN NULL,
                audio BOOLEAN NULL,
                length INTEGER NULL,
                mime varchar(512) NULL,
                image BOOLEAN NULL,
                video_codec varchar(512) NULL,
                FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
            ");

            Ctx::$database->create_table("scheduled_images_metadata", "
                schedule_id INTEGER NOT NULL,
                key TEXT,
                value TEXT,
                FOREIGN KEY (schedule_id) REFERENCES scheduled_images(id) ON DELETE CASCADE
            ");

            $this->set_version(1);
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($event->metadata->get("schedule") == "on") {
            if (!$this->check_contents($event->tmpname)) {
                // We DO support this extension - but the file looks corrupt
                throw new UploadException("Invalid or corrupted file");
            }

            $existing = Image::by_hash($event->tmpname->md5());
            if (!is_null($existing)) {
                if (Ctx::$config->get(UploadConfig::COLLISION_HANDLER) === 'merge') {
                    // Right now tags are the only thing that get merged, so
                    // we can just send a TagSetEvent - in the future we might
                    // want a dedicated MergeEvent?
                    if (!empty($event->metadata['tags'])) {
                        $tags = Tag::explode($existing->get_tag_list() . " " . $event->metadata['tags']);
                        send_event(new TagSetEvent($existing, $tags));
                    }
                    $event->images[] = $existing;
                    return;
                } else {
                    throw new UploadException(">>{$existing->id} already has hash {$existing->hash}");
                }
            }

            // Create a new Image object
            $filename = $event->tmpname;
            assert($filename->is_readable());
            $image = new Image();
            $image->tmp_file = $filename;
            $filesize = $filename->filesize();
            if ($filesize === 0) {
                throw new UploadException("File size is zero");
            }
            $image->filesize = $filesize;
            $image->hash = $filename->md5();
            // DB limits to 255 char filenames
            $image->filename = substr($event->filename, -250);
            $image->set_mime($event->mime);
            try {
                send_event(new MediaCheckPropertiesEvent($image));
            } catch (MediaException $e) {
                throw new UploadException("Unable to scan media properties {$filename->str()} / {$image->filename} / $image->hash: ".$e->getMessage());
            }
            $latest = $this->get_latest();

            $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL, ConfigType::INT);
            $diff = time() - $latest;
            $base = Image::IMAGE_DIR;
            if ($diff > $interval) {
                $image->save_to_db(); // Ensure the image has a DB-assigned ID

                $iae = send_event(new ImageAdditionEvent($image));
                send_event(new ImageInfoSetEvent($image, $event->slot, $event->metadata));
                $image = $iae->image;
            } else {
                $this->schedule_image($image, $event->metadata, $event->slot); // Ensure the image has a DB-assigned ID
                $base = "scheduled_images";
                \safe\exec("php ext/post_scheduling/timer.php $interval > /dev/null 2>&1 &");
            }

            // If everything is OK, then move the file to the archive
            $filename = Filesystem::warehouse_path($base, $event->hash);
            try {
                $event->tmpname->copy($filename);
            } catch (\Exception $e) {
                throw new UploadException("Failed to copy file from uploads ({$event->tmpname->str()}) to archive ({$filename->str()}): ".$e->getMessage());
            }

            $event->images[] = $image;
            $event->stop_processing = true;
        }
    }

    private function get_latest(): int
    {
        return \Safe\strtotime(Ctx::$database->get_one("
            SELECT posted FROM images
            ORDER BY id DESC
            LIMIT 1;
        "));
    }

    public function get_scheduled_post(): int
    {
        $latest = $this->get_latest();

        $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL, ConfigType::INT);
        $diff = time() - $latest;
        if ($diff >= $interval) {
            $scheduled = Ctx::$database->get_row("
                SELECT * FROM scheduled_images
                ORDER BY id ASC
                LIMIT 1;
            ");
            if (is_null($scheduled)) {
                return -1;
            }

            Ctx::$user = User::by_id($scheduled["owner_id"]);
            $_SERVER['REMOTE_ADDR'] = $scheduled["owner_ip"];
            $path = Filesystem::warehouse_path("scheduled_images", $scheduled["hash"]);

            $args = ["id" => $scheduled["id"]];
            $metadata = new QueryArray(Ctx::$database->get_pairs("
                SELECT key, value FROM scheduled_images_metadata
                WHERE schedule_id = :id;
            ", $args));

            Ctx::$database->execute(
                "DELETE FROM scheduled_images_metadata WHERE schedule_id = :id",
                $args
            );
            Ctx::$database->execute(
                "DELETE FROM scheduled_images WHERE id = :id",
                $args
            );

            try {
                Ctx::$database->with_savepoint(function () use ($path, $scheduled, $metadata, $args) {
                    $event = send_event(new DataUploadEvent($path, $scheduled["filename"], 0, $metadata));
                    if (count($event->images) === 0) {
                        throw new UploadException("MIME type not supported: " . $event->mime);
                    }
                });

            } catch (UploadException $ex) {
                Log::error("post_schedule", "Image with hash ".$scheduled["hash"]." failed to upload, tags: ". $metadata->get("tags"));
                return 1;
            }

            $more = Ctx::$database->get_one("SELECT count(id) > 0 FROM scheduled_images");
            if (!$more) {
                return -1;
            }
            return $interval;
        }
        return $interval - $diff;

    }

    public function onUploadCommonBuilding(UploadCommonBuildingEvent $event): void
    {
        $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL);
        $event->add_part(TR(
            TH(["width" => "20"], "Schedule?"),
            TD(["colspan" => "6"], INPUT(["name" => "schedule", "type" => "checkbox"]), " ($interval seconds)")
        ), 10);
    }

    private function schedule_image(Image $image, QueryArray $metadata, int $slot): void
    {
        $props_to_save = [
            "filename" => substr($image->filename, 0, 255),
            "filesize" => $image->filesize,
            "hash" => $image->hash,
            "mime" => (string)$image->get_mime(),
            "ext" => strtolower($image->get_ext()),
            "source" => $image->source,
            "width" => $image->width,
            "height" => $image->height,
            "lossless" => $image->lossless,
            "video" => $image->video,
            "video_codec" => $image->video_codec?->value,
            "image" => $image->image,
            "audio" => $image->audio,
            "length" => $image->length
        ];
        $props_to_save["owner_id"] = Ctx::$user->id;
        $props_to_save["owner_ip"] = Network::get_real_ip();
        $props_to_save["posted"] = date('Y-m-d H:i:s', time());

        $props_sql = implode(", ", array_keys($props_to_save));
        $vals_sql = implode(", ", array_map(fn ($prop) => ":$prop", array_keys($props_to_save)));

        Ctx::$database->execute(
            "INSERT INTO scheduled_images($props_sql) VALUES ($vals_sql)",
            $props_to_save,
        );
        $schedule_id = Ctx::$database->get_last_insert_id('scheduled_images_id_seq');
        $image->id = $slot;

        $slotted_params = [];
        $metarray = $metadata->toArray();
        if (isset($metarray["auth_token"])) {
            unset($metarray["auth_token"]);
        }
        if (isset($metarray["schedule"])) {
            unset($metarray["schedule"]);
        }
        foreach ($metarray as $key => $value) {
            if (\safe\preg_match('/(.+?)(\d+)$/', $key, $matches)) {
                if ((int)$matches[2] === $slot) {
                    $slotted_params[$matches[1]] = $value;
                }
            } else {
                if (!isset($slotted_params[$key])) {
                    $slotted_params[$key] = $value;
                }
            }
        }
        foreach ($slotted_params as $key => $value) {
            Ctx::$database->execute(
                "INSERT INTO scheduled_images_metadata(schedule_id, key, value) VALUES (:id, :key, :value)",
                ["id" => $schedule_id, "key" => $key, "value" => $value],
            );
        }
    }

    public function get_priority(): int
    {
        return 30;
    }

    // we don't do this
    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
    }

    protected function check_contents(Path $tmpname): bool
    {
        return true;
    }

    protected function create_thumb(Image $image): bool
    {
        return true;
    }
}
