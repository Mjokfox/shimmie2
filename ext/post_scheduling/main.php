<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, TD, TH, TR};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @extends DataHandlerExtension<PostSchedulingTheme> */
final class PostScheduling extends DataHandlerExtension
{
    public const KEY = "post_scheduling";
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {

            Ctx::$database->create_table("scheduled_posts", "
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

            Ctx::$database->create_table("scheduled_posts_metadata", "
                schedule_id INTEGER NOT NULL,
                key TEXT,
                value TEXT,
                FOREIGN KEY (schedule_id) REFERENCES scheduled_posts(id) ON DELETE CASCADE
            ");

            $this->set_version(1);
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($event->metadata->get("schedule") === "on") {
            if (!$this->check_contents($event->tmpname)) {
                // We DO support this extension - but the file looks corrupt
                throw new UploadException("Invalid or corrupted file");
            }
            $hash = $event->tmpname->md5();
            $existing = Image::by_hash($hash);
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

            $res = Ctx::$database->get_one(
                "
                SELECT id FROM scheduled_posts
                WHERE hash = :hash
                ",
                ["hash" => $hash]
            );

            if (!is_null($res)) {
                throw new UploadException("Currently scheduled post #{$res} already has hash {$hash}");
            }

            $latest = $this->get_latest();
            $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL, ConfigType::INT);
            $diff = time() - $latest;

            if (self::count_scheduled_posts() === 0 && $diff > $interval) { // Upload it immediatly
                $meta = new QueryArray($event->metadata->toArray());
                $meta->set("schedule", "");
                $due = send_event(new DataUploadEvent($event->tmpname, $event->filename, $event->slot, $meta)); // this isnt cursed
                $event->images = array_merge($event->images, $due->images);
            } else { // schedule it
                $filename = $event->tmpname;
                assert($filename->is_readable());
                $image = new Image();
                $image->tmp_file = $filename;
                $filesize = $filename->filesize();
                if ($filesize === 0) {
                    throw new UploadException("File size is zero");
                }
                $image->filesize = $filesize;
                $image->hash = $hash;
                // DB limits to 255 char filenames
                $image->filename = substr($event->filename, -250);
                $image->set_mime($event->mime);
                try {
                    send_event(new MediaCheckPropertiesEvent($image));
                } catch (MediaException $e) {
                    throw new UploadException("Unable to scan media properties {$filename->str()} / {$image->filename} / $image->hash: ".$e->getMessage());
                }

                $this->schedule_image($image, $event->metadata, $event->slot);
                \Safe\exec("bash ext/post_scheduling/timer.sh -t $interval -d $interval > /dev/null 2>&1 &");

                // If everything is OK, then move the file to the archive
                $filename = Filesystem::warehouse_path(PostSchedulingConfig::BASE, $event->hash);
                try {
                    $event->tmpname->copy($filename);
                } catch (\Exception $e) {
                    throw new UploadException("Failed to copy file from uploads ({$event->tmpname->str()}) to archive ({$filename->str()}): ".$e->getMessage());
                }
                Ctx::$cache->delete("scheduled_post_count");
                Ctx::$page->flash("Scheduled {$event->filename};");
                $event->images[] = $image;
            }
            $event->stop_processing = true;
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("post_schedule/list", permission: ReplaceFilePermission::REPLACE_IMAGE)) {
            $posts = Ctx::$database->get_all("SELECT * FROM scheduled_posts;");
            $meta = Ctx::$database->get_all("SELECT * FROM scheduled_posts_metadata;");

            $metadata = [];
            foreach ($meta as $row) {
                if (!isset($metadata[$row["schedule_id"]])) {
                    $metadata[$row["schedule_id"]] = [];
                }
                $metadata[$row["schedule_id"]][$row["key"]] = $row["value"];
            }
            $images = array_map(function ($row) use ($metadata): Image {
                $row = array_merge($row, $metadata[$row["id"]] ?? []);
                $image = new Image($row);
                $image->tag_array = Tag::explode($row["tags"] ?? "");
                return $image;
            }, $posts);

            $this->theme->display_scheduled_posts($images);
        } elseif ($event->page_matches("post_schedule/remove", method: "POST", permission: ReplaceFilePermission::REPLACE_IMAGE)) {
            $id = $event->POST->req("id");
            $arg = ["id" => $id];
            $hash = Ctx::$database->get_one("SELECT hash FROM scheduled_posts WHERE id = :id", $arg);
            Ctx::$database->execute("DELETE FROM scheduled_posts_metadata WHERE schedule_id = :id", $arg);
            Ctx::$database->execute("DELETE FROM scheduled_posts WHERE id = :id", $arg);
            Ctx::$cache->delete("scheduled_post_count");
            try {
                Filesystem::warehouse_path(PostSchedulingConfig::BASE, $hash)->unlink();
            } catch (\Exception $e) {
                // the file already is gone
            }

            Ctx::$page->flash("Scheduled post deleted");
            Ctx::$page->set_redirect(make_link("post_schedule/list"));
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block($this->count_scheduled_posts());
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('check-post-scheduler')
            ->setDescription('Checks the current schedule queue, uploads if possible, and returns the time to wait for the next post')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $output->write((string)$this->get_scheduled_post());
                return Command::SUCCESS;
            });
    }

    private function get_latest(): int
    {
        $order = Ctx::$config->get(IndexConfig::ORDER);
        // @phpstan-ignore-next-line
        return \Safe\strtotime(Ctx::$database->get_one("
            SELECT posted FROM images
            ORDER BY $order
            LIMIT 1;
        "));
    }

    public function get_scheduled_post(): int
    {
        $scheduled = Ctx::$database->get_row("
            SELECT * FROM scheduled_posts
            ORDER BY id ASC
            LIMIT 1;
        ");
        if (is_null($scheduled)) {
            return -1;
        }

        $latest = $this->get_latest();
        /** @var int $interval */
        $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL, ConfigType::INT);
        $diff = time() - $latest;
        if ($diff >= $interval) {
            Ctx::$user = User::by_id($scheduled["owner_id"]);
            $_SERVER['REMOTE_ADDR'] = $scheduled["owner_ip"];
            $path = Filesystem::warehouse_path(PostSchedulingConfig::BASE, $scheduled["hash"]);

            $args = ["id" => $scheduled["id"]];
            $metadata = new QueryArray(Ctx::$database->get_pairs("
                SELECT key, value FROM scheduled_posts_metadata
                WHERE schedule_id = :id;
            ", $args));

            Ctx::$database->execute(
                "DELETE FROM scheduled_posts_metadata WHERE schedule_id = :id",
                $args
            );
            Ctx::$database->execute(
                "DELETE FROM scheduled_posts WHERE id = :id",
                $args
            );

            try {
                Ctx::$database->with_savepoint(function () use ($path, $scheduled, $metadata) {
                    $event = send_event(new DataUploadEvent($path, $scheduled["filename"], 0, $metadata));
                    if (count($event->images) === 0) {
                        throw new UploadException("MIME type not supported: " . $event->mime);
                    }
                });

            } catch (UploadException $ex) {
                Log::error("post_schedule", "Image with hash ".$scheduled["hash"]." failed to upload, tags: ". $metadata->get("tags"));
                return 1;
            }
            Ctx::$cache->delete("scheduled_post_count");
            Filesystem::warehouse_path(PostSchedulingConfig::BASE, $scheduled["hash"])->unlink();
            $more = Ctx::$database->get_one("SELECT count(id) > 0 FROM scheduled_posts");
            if (!$more) {
                return -1;
            }
            return $interval;
        }
        return $interval - $diff;
    }

    public function count_scheduled_posts(): int
    {
        return (int)cache_get_or_set(
            "scheduled_post_count",
            fn () => Ctx::$database->get_one("SELECT count(id) FROM scheduled_posts"),
            900
        );
    }

    public function onUploadCommonBuilding(UploadCommonBuildingEvent $event): void
    {
        $interval = Ctx::$config->get(PostSchedulingConfig::SCHEDULE_INTERVAL);
        $time = [];
        if ($interval > 86400) {
            $time[] = floor($interval / 86400) . " days";
            $interval %= 86400;
        }
        if ($interval > 3600) {
            $time[] = floor($interval / 3600) . " hours";
            $interval %= 3600;
        }
        if ($interval > 60) {
            $time[] = floor($interval / 60) . " min";
            $interval %= 60;
        }
        if ($interval > 0) {
            $time[] = "$interval sec";
        }
        $strtime = join(", ", $time);
        $count = $this->count_scheduled_posts();
        $event->add_part(TR(
            TH(["width" => "20"], "Schedule?"),
            TD(["colspan" => "6"], INPUT(["name" => "schedule", "type" => "checkbox"]), " (current queue: $count posts, with $strtime interval)")
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
        $props_to_save["owner_ip"] = (string)Network::get_real_ip();
        $props_to_save["posted"] = date('Y-m-d H:i:s', time());

        $props_sql = implode(", ", array_keys($props_to_save));
        $vals_sql = implode(", ", array_map(fn ($prop) => ":$prop", array_keys($props_to_save)));

        Ctx::$database->execute(
            "INSERT INTO scheduled_posts($props_sql) VALUES ($vals_sql)",
            $props_to_save,
        );
        $schedule_id = Ctx::$database->get_last_insert_id('scheduled_posts_id_seq');
        $image->id = $slot + 1;

        $slotted_params = [];
        $metarray = $metadata->toArray();
        if (isset($metarray["auth_token"])) {
            unset($metarray["auth_token"]);
        }
        if (isset($metarray["schedule"])) {
            unset($metarray["schedule"]);
        }
        foreach ($metarray as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if (\Safe\preg_match('/(.+?)(\d+)$/', $key, $matches)) {
                if ((int)$matches[2] === $slot) {
                    $key = $matches[1];
                } else {
                    continue;
                }
            }
            if (is_array($value)) {
                $value = implode(" ", $value);
            }
            if (!isset($slotted_params[$key])) {
                $slotted_params[$key] = $value;
            } else {
                $slotted_params[$key] = "{$slotted_params[$key]} $value";
            }
        }
        foreach ($slotted_params as $key => $value) {
            Ctx::$database->execute(
                "INSERT INTO scheduled_posts_metadata(schedule_id, key, value) VALUES (:id, :key, :value)",
                ["id" => $schedule_id, "key" => $key, "value" => $value]
            );
        }
    }

    public function get_priority(): int
    {
        return 30;
    }

    // we don't do this
    protected function media_check_properties(Image $image): ?MediaProperties
    {
        return null;
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
