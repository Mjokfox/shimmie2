<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, INPUT, TABLE, TD, TR, rawHTML};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

class FlickrSource extends Extension
{
    public const KEY = "flickr_source";

    #[EventListener(priority: 2)]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $start_id = Ctx::$database->get_one("SELECT max(id)-100 from images;");
        $html = (string)SHM_SIMPLE_FORM(
            make_link("admin/flickr_source"),
            TABLE(
                TR(
                    TD(["style" => "padding-right:5px"], B("Start id")),
                    TD(INPUT(["type" => 'number', "name" => 'id_offset', "value" => $start_id, "style" => "width:5em"])),
                ),
                TR(
                    TD(B("Limit")),
                    TD(INPUT(["type" => 'number', "name" => 'limit', "value" => "100", "style" => "width:5em"])),
                ),
            ),
            SHM_SUBMIT('Find all flickr sources'),
        );
        Ctx::$page->add_block(new Block("Flickr Source", rawHTML($html)));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "flickr_source":
                $start_time = ftime();
                $offset = $event->params['id_offset'] ?: "0";
                $limit = $event->params['limit'] ?: "100";
                /** @var array{array{id:int,filename:string}} $files  */
                $files = Ctx::$database->get_all(
                    "SELECT * FROM images
                    WHERE (source IS NULL OR source LIKE '%live.staticflickr%')
                    AND mime LIKE 'image/%'
                    AND id > :id_offset
                    LIMIT :limit;",
                    ["id_offset" => $offset, "limit" => $limit]
                );
                $res = $this->find_sources($files, [$this, "image_update"]);

                if (PostSchedulingInfo::is_enabled()) {
                    /** @var array{array{id:int,filename:string}} $files  */
                    $files = Ctx::$database->get_all(
                        "SELECT * FROM scheduled_posts sp
                        LEFT JOIN scheduled_posts_metadata spm ON spm.schedule_id = sp.id AND spm.key = 'source'
                        WHERE spm.schedule_id IS NULL;"
                    );
                    $res1 = $this->find_sources($files, [$this, "schedule_image_update"]);
                    $res["passed"] += $res1["passed"];
                    $res["failed"] = array_merge($res["failed"], $res1["failed"]);
                    $res["not"] += $res1["not"];
                }

                $exec_time = round(ftime() - $start_time, 2);
                $message = "Found valid sources for {$res["passed"]} images, invalid sources for ".count($res["failed"]).", and skipped {$res["not"]} non flickr images, which took $exec_time seconds." . (count($res["failed"]) > 0 ? " Failed: " . implode(", ", $res["failed"]) : "");
                Log::info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('flickr_source')
            ->setDescription('Find flickr sources for the newest default 100 posts, requires -u user argument, where user has permission to change the source')
            ->addArgument('amount', InputArgument::OPTIONAL)
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $start_time = ftime();
                $limit = $input->getArgument('amount') ?? 100;
                /** @var array{array{id:int,filename:string}} $files  */
                $files = Ctx::$database->get_all(
                    "SELECT * FROM images
                    WHERE (source IS NULL OR source LIKE '%live.staticflickr%')
                    AND mime LIKE 'image/%'
                    ORDER BY id DESC
                    LIMIT :limit;",
                    ["limit" => $limit]
                );
                $res = $this->find_sources($files, [$this, "image_update"]);
                $exec_time = round(ftime() - $start_time, 2);
                $message = "passed: {$res["passed"]}, invalid: ".count($res["failed"]).", skipped: {$res["not"]}, time: $exec_time seconds." . (count($res["failed"]) > 0 ? " Failed: " . implode(", ", $res["failed"]) : "");
                $output->write($message);
                return Command::SUCCESS;
            });
    }

    /**
     * @param array{array{id:int,filename:string}} $files
     * @return array{passed:int,failed:array<int>,not:int}
     */
    private function find_sources(array $files, callable $func): array
    {
        $passed = 0;
        $failed = [];
        $not = 0;
        $process_body = PostTitlesInfo::is_enabled() || PostDescriptionInfo::is_enabled();
        foreach ($files as $file) {
            if (!\Safe\preg_match("/(\d{7,13})_[a-f0-9]{7,13}_[a-z0-9]{1,2}(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                if (!\Safe\preg_match("/[a-zA-Z\-]+_(\d{7,13})_o(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                    $not++;
                    continue;
                }
            }
            $data = $this->get_Flickr_data($matches[1], $process_body);
            $source = $data["source"];

            if (is_null($source)) {
                $not++;
            } elseif ($source === "https://flickr.com/photos///") {
                $source = 'Unknown: broken flickr link';
                $failed[] = $file["id"];
            } elseif (str_starts_with($source, "https://identity")) {
                $source = 'Unknown: private flickr link';
                $failed[] = $file["id"];
            } else {
                $passed++;
            }
            $func($file, $data);
        }
        return ["passed" => $passed, "failed" => $failed, "not" => $not];
    }

    /** @param array{id:int,filename:string} $file
     * @param array{description: string, title: string, source: string} $data
    */
    private function image_update(array $file, array $data): void
    {
        $image = new Post($file);
        send_event(new SourceSetEvent($image, $data["source"]));

        if (PostTitlesInfo::is_enabled() && !empty($data["title"])) {
            if (empty($image['title'])) {
                send_event(new PostTitleSetEvent($image, $data["title"]));
            }
        }

        if (PostDescriptionInfo::is_enabled() && !empty($data["description"])) {
            $description = (string) Ctx::$database->get_one(
                "SELECT description FROM image_descriptions WHERE image_id = :id",
                ["id" => $image->id]
            ) ?: null;
            if (empty($description)) {
                send_event(new PostDescriptionSetEvent($image->id, $data["description"]));
            }
        }
    }

    /** @param array{id:int,filename:string} $file */
    private function schedule_image_update(array $file, string $source): void
    {
        Ctx::$database->execute(
            "INSERT INTO scheduled_posts_metadata(schedule_id, key, value) 
            VALUES (:id, 'source', :value)",
            ["id" => $file["id"], "value" => $source]
        );
    }

    /** @return array{source: ?string, title: ?string, description: ?string} */
    private function get_Flickr_data(int|string $id, bool $process_body): array
    {
        $ch = curl_init("https://flickr.com/photo.gne?id=$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (!$process_body) { // we only need the header
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $output = [
            "source" => null,
            "title" => null,
            "description" => null
        ];

        /** @var false|string $response  */
        $response = curl_exec($ch);
        if ($response !== false) {
            $output["source"] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if ($process_body) {
                if (preg_match("/<meta name=\"description\" content=\"(.*?)\"  data-dynamic=\"true\">/s", $response, $matches)) {
                    $output["description"] = $this->sanitize_output(html_entity_decode($matches[1]));
                }
                if (preg_match("/<meta name=\"title\" content=\"(.*?)\"  data-dynamic=\"true\">/s", $response, $matches)) {
                    $output["title"] = $this->sanitize_output(html_entity_decode($matches[1]));
                }
            }
        }

        return $output;
    }

    private function sanitize_output(string $s): ?string
    {
        $s = preg_replace('/<a href=\"(.*?)\".*?<\/a>/is', '$1', $s);
        return preg_replace('/<.+?>/im', '$1', $s ?? "");
    }
}
