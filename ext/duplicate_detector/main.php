<?php

declare(strict_types=1);

namespace Shimmie2;

use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\{AverageHash, BlockHash, DifferenceHash, PerceptualHash};

use function MicroHTML\{B, BUTTON, INPUT, TABLE, TD, TR, emptyHTML};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @phpstan-type PhashArr array{average:?string,difference:?string,perceptual:?string,blockhash:?string}
 * @extends Extension<DuplicateDetectorTheme>
 */
class DuplicateDetector extends Extension
{
    public const KEY = "duplicate_detector";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            Ctx::$database->create_table(
                'image_phashes',
                'image_id INTEGER NOT NULL,
                ahash bit(64),
                dhash bit(64),
                phash bit(64),
                blockhash bit(64),
                FOREIGN KEY(image_id) REFERENCES images(id) ON DELETE CASCADE,
                PRIMARY KEY(image_id)'
            );
            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("duplicate_replace/{image_id}", method: "GET", permission: DuplicateDetectorPermission::REPLACE_DUPLICATE)) {
            $this->theme->display_replace_page($event->get_iarg('image_id'));
        } elseif ($event->page_matches("duplicate_replace/{image_id}", method: "POST", permission: DuplicateDetectorPermission::REPLACE_DUPLICATE)) {
            $image_id = $event->get_iarg('image_id');
            $image = Post::by_id_ex($image_id);

            if (!empty($event->POST->get("url"))) {
                $tmp_filename = shm_tempnam("transload");
                $url = $event->POST->req("url");
                assert(!empty($url));
                Network::fetch_url($url, $tmp_filename);
            } elseif (count($_FILES) > 0) {
                $tmp_filename = new Path($_FILES["data"]['tmp_name']);
            } else {
                Ctx::$page->set_redirect(make_link("duplicate_replace/$image_id"));
                return;
            }
            if ($tmp_filename->filesize() > Ctx::$config->get(UploadConfig::SIZE)) {
                $size = to_shorthand_int($tmp_filename->filesize());
                $limit = to_shorthand_int(Ctx::$config->get(UploadConfig::SIZE));
                throw new UploadException("File too large ($size > $limit)");
            }

            $dce = send_event(new DuplicateCheckEvent($tmp_filename, $image_id));
            if (!$dce->is_duplicate) {
                throw new InvalidInput("The image you've given is not similar enough to be a duplicate. If you think this is incorrect, please report the duplicate post with your source");
            }

            send_event(new MediaReplaceEvent($image, $tmp_filename));
            if ($event->POST->get("source")) {
                send_event(new SourceSetEvent($image, $event->POST->req("source")));
            }
            Ctx::$cache->delete("thumb-block:{$image_id}");
            Ctx::$page->set_redirect(make_link("post/view/$image_id"));
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $current = Ctx::$database->get_one("SELECT count(image_id) FROM image_phashes");
        $all = Ctx::$database->get_one("SELECT count(id) FROM images WHERE image = TRUE");
        $html = emptyHTML(
            SHM_SIMPLE_FORM(
                make_link("admin/fill_phashes"),
                TABLE(
                    TR(
                        TD(["style" => "padding-right:5px"], B("Start id")),
                        TD(INPUT(["type" => 'number', "name" => 'start_id', "value" => "0", "style" => "width:5em"])),
                    ),
                    TR(
                        TD(B("Limit")),
                        TD(INPUT(["type" => 'number', "name" => 'limit', "value" => "100", "style" => "width:5em"])),
                    ),
                ),
                SHM_SUBMIT('Get phashes'),
            ),
            "populated: $current/$all",
            SHM_SIMPLE_FORM(
                make_link("admin/clear_phashes"),
                BUTTON(['type' => 'button', 'onclick' => 'if(window.confirm("Are you sure you want to delete all phashes"))this.form.submit()'], 'Clear all phashes')
            )
        );
        Ctx::$page->add_block(new Block("Duplicate detector", $html));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "fill_phashes":
                $start_time = ftime();
                $this->fill_phashes((int)$event->params['start_id'], (int)$event->params['limit']);
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Added image features to the database for {$event->params['limit']} images in $exec_time seconds";
                Log::info("admin", $message, $message);
                break;
            case 'clear_phashes':
                Ctx::$database->execute("DELETE FROM image_phashes");
                $message = "Deleted all image phashes";
                Log::info("admin", $message, $message);
                break;
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('fill_image_phashes')
            ->addArgument("start_id", InputArgument::OPTIONAL, default: 0)
            ->addArgument("limit", InputArgument::OPTIONAL, default: 100)
            ->setDescription('Generate image phashes')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $_SERVER['DOCUMENT_ROOT'] = getcwd();
                $start_time = ftime();
                $this->fill_phashes($input->getArgument('start_id'), $input->getArgument('limit'));
                $exec_time = round(ftime() - $start_time, 2);
                $output->write("Took $exec_time seconds");
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onImageInfoSet(PostInfoSetEvent $event): void
    {
        if (!$event->image->image) {
            return;
        }
        $exists = Ctx::$database->get_one("SELECT 1 FROM image_features WHERE image_id = :id", ["id" => $event->image->id]);
        if (is_null($exists)) {
            $phashes = $this->generate_phashes($event->image->get_media_filename()->str());
            $this->add_phash_to_db($event->image->id, $phashes);
        }
    }

    #[EventListener]
    public function onUploadAction(UploadActionEvent $event): void
    {
        $phashes = $this->generate_phashes($event->file->str());
        $ids = $this->find_closest_by_phash($phashes, 1);
        if (\count($ids) > 0) {
            $image = Post::by_id($ids[0]["image_id"]);
            if (is_null($image)) {
                return;
            }
            $event->output["duplicate_detection"] = [
                "distance" => $ids[0]["distance"],
                "threshold" => Ctx::$config->get(DuplicateDetectorConfig::HAMMING_DISTANCE_THRESHOLD),
                "image_id" => $ids[0]["image_id"],
                "image_data" => [
                    "link" => $image->get_media_link()->getPath(),
                    "width" => $image->width,
                    "height" => $image->height,
                    "filesize" => $image->filesize
                ]
            ];
        }
    }

    #[EventListener]
    public function onDuplicateCheck(DuplicateCheckEvent $event): void
    {
        if ($event->is_duplicate) {
            return;
        }
        $phashes = $this->generate_phashes($event->file->str());
        $distance = $this->get_distance_from_post($event->image_id, $phashes);
        if ($distance <= Ctx::$config->get(DuplicateDetectorConfig::HAMMING_DISTANCE_THRESHOLD)) {
            $event->is_duplicate = true;
            $event->stop_processing = true;
        }
    }

    private function fill_phashes(int|string $start_id = 0, int|string $limit = 100): void
    {
        $query = "SELECT a.id, a.hash
        FROM images a
        LEFT JOIN image_phashes b
            ON a.id = b.image_id
        WHERE b.image_id IS NULL
        AND a.image = TRUE
        AND a.id > :id
        ORDER BY a.id ASC
        LIMIT :limit;";
        $images = Ctx::$database->get_all($query, ["id" => $start_id, "limit" => $limit]);
        foreach ($images as $image) {
            $phashes = $this->generate_phashes($_SERVER['DOCUMENT_ROOT'] ."/" . Filesystem::warehouse_path(Post::MEDIA_DIR, $image["hash"])->str());
            $this->add_phash_to_db($image["id"], $phashes);
        }
    }

    /** @return PhashArr */
    private function generate_phashes(string $path): array
    {
        static $hashers = [];
        if (empty($hashers)) {
            $enabled = Ctx::$config->get(DuplicateDetectorConfig::ENABLED_ALGORITHMS);
            foreach ($enabled as $alg) {
                $hashers[$alg] = match ($alg) { // @phpstan-ignore-line
                    "average" => new ImageHash(new AverageHash()),
                    "difference" => new ImageHash(new DifferenceHash()),
                    "perceptual" => new ImageHash(new PerceptualHash()),
                    "blockhash" => new ImageHash(new BlockHash()),
                    default => new ImageHash(new DifferenceHash())
                };
            }
        }
        $out = [
            "average" => null,
            "difference" => null,
            "perceptual" => null,
            "blockhash" => null,
        ];
        foreach ($hashers as $name => $hasher) {
            $out[$name] = $hasher->hash($path)->toBits();
        }
        /** @var PhashArr $out */
        return $out;
    }

    /**
     * @param PhashArr $phashes
     */
    private function add_phash_to_db(int $id, array $phashes): void
    {
        Ctx::$database->execute(
            "INSERT INTO image_phashes VALUES(:id, :ahash, :dhash, :phash, :blockhash)",
            [
                "id" => $id,
                "ahash" => $phashes["average"],
                "dhash" => $phashes["difference"],
                "phash" => $phashes["perceptual"],
                "blockhash" => $phashes["blockhash"],
                ]
        );
    }

    /**
     * @param PhashArr $phashes
     */
    private function get_distance_from_post(int $image_id, array $phashes): int
    {
        return Ctx::$database->get_one(
            "SELECT LEAST (
                bit_count(ahash # :ahash), 
                bit_count(dhash # :dhash),
                bit_count(phash # :phash),
                bit_count(blockhash # :blockhash)
            )
            FROM image_phashes
            WHERE image_id = :image_id",
            [
                "ahash" => $phashes["average"],
                "dhash" => $phashes["difference"],
                "phash" => $phashes["perceptual"],
                "blockhash" => $phashes["blockhash"],
                "image_id" => $image_id
            ]
        );
    }

    /**
     * @return array{image_id:int,distance:int}[]
     */
    private function find_similar_by_image_id(int $id, ?int $limit = null): array
    {
        /** @var array{image_id:int,distance:int}[] */
        return Ctx::$database->get_all(
            "SELECT ip.image_id,
            LEAST (
                bit_count(ip.ahash # iq.ahash), 
                bit_count(ip.dhash # iq.dhash),
                bit_count(ip.phash # iq.phash),
                bit_count(ip.blockhash # iq.blockhash)
            ) AS distance
            FROM image_phashes ip
            JOIN image_phashes iq ON iq.image_id = :id
            WHERE NOT ip.image_id = :id
            ORDER BY distance
            LIMIT :limit",
            ["id" => $id, "limit" => $limit ?? Ctx::$config->get(DuplicateDetectorConfig::DEFAULT_LIMIT)]
        );
    }

    /**
     * @param PhashArr $phashes
     * @return array{image_id:int,distance:int}[]
     */
    private function find_closest_by_phash(array $phashes, ?int $limit = null): array
    {
        /** @var array{image_id:int,distance:int}[] */
        return Ctx::$database->get_all(
            "SELECT image_id, LEAST (
                bit_count(ahash # :ahash), 
                bit_count(dhash # :dhash),
                bit_count(phash # :phash),
                bit_count(blockhash # :blockhash)
            ) AS distance
            FROM image_phashes
            ORDER BY distance
            LIMIT :limit",
            [
                "ahash" => $phashes["average"],
                "dhash" => $phashes["difference"],
                "phash" => $phashes["perceptual"],
                "blockhash" => $phashes["blockhash"],
                "limit" => $limit ?? Ctx::$config->get(DuplicateDetectorConfig::DEFAULT_LIMIT)]
        );
    }
}
