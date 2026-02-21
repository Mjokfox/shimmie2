<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, INPUT, TABLE, TD, TR, rawHTML};

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
                $res = $this->findSources($files, [$this, "imageUpdate"]);

                if (PostSchedulingInfo::is_enabled()) {
                    /** @var array{array{id:int,filename:string}} $files  */
                    $files = Ctx::$database->get_all(
                        "SELECT * FROM scheduled_posts sp
                        LEFT JOIN scheduled_posts_metadata spm ON spm.schedule_id = sp.id AND spm.key = 'source'
                        WHERE spm.schedule_id IS NULL;"
                    );
                    $res1 = $this->findSources($files, [$this, "scheduleImageUpdate"]);
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

    /**
     * @param array{array{id:int,filename:string}} $files
     * @return array{passed:int,failed:array<int>,not:int}
     */
    private function findSources(array $files, callable $func): array
    {
        $passed = 0;
        $failed = [];
        $not = 0;
        foreach ($files as $file) {
            if (!\Safe\preg_match("/(\d{7,13})_[a-f0-9]{7,13}_[a-z0-9]{1,2}(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                if (!\Safe\preg_match("/[a-zA-Z\-]+_(\d{7,13})_o(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                    $not++;
                    continue;
                }
            }
            $source = $this->getFlickrUrl($matches[1]);
            if ($source !== "https://flickr.com/photos///") {
                $func($file, $source);
                $passed++;
            } else {
                $failed[] = $file["id"];
            }
        }
        return ["passed" => $passed, "failed" => $failed, "not" => $not];
    }

    /** @param array{id:int,filename:string} $file */
    private function imageUpdate(array $file, string $source): void
    {
        $image = new Post($file);
        send_event(new SourceSetEvent($image, $source));
    }

    /** @param array{id:int,filename:string} $file */
    private function scheduleImageUpdate(array $file, string $source): void
    {
        Ctx::$database->execute(
            "INSERT INTO scheduled_posts_metadata(schedule_id, key, value) 
            VALUES (:id, 'source', :value)",
            ["id" => $file["id"], "value" => $source]
        );
    }

    private function getFlickrUrl(int|string $id): string
    {
        $url = "https://flickr.com/photo.gne?id={$id}";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        curl_exec($ch);

        $redirectedUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        return $redirectedUrl;
    }
}
