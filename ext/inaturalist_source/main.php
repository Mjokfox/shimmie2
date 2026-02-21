<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, INPUT, TABLE, TD, TR, rawHTML};

class INatSource extends Extension
{
    public const KEY = "inaturalist_source";
    private const REGEX = "/inaturalist_(\d+)_(\d+)_(\d+)\./";

    #[EventListener]
    public function onImageInfoSet(PostInfoSetEvent $event): void
    {
        if (!($event->params["source"] || $event->params["source{$event->slot}"])) {
            $image = $event->image;
            if (\Safe\preg_match(self::REGEX, \basename($image->filename), $matches)) {
                $source = $this->shapeSource($matches[1]);
                send_event(new SourceSetEvent($image, $source));
            }
        }
    }

    #[EventListener(priority: 2)]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $start_id = Ctx::$database->get_one("SELECT max(id)-100 from images;");
        $html = (string)SHM_SIMPLE_FORM(
            make_link("admin/inaturalist_source"),
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
            SHM_SUBMIT('Find all INaturalist sources'),
        );
        Ctx::$page->add_block(new Block("INaturalist Source", rawHTML($html)));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "inaturalist_source":
                $start_time = ftime();
                $offset = $event->params['id_offset'] ?: "0";
                $limit = $event->params['limit'] ?: "100";
                /** @var array{array{id:int,filename:string}} $files  */
                $files = Ctx::$database->get_all(
                    "SELECT * FROM images
                    WHERE source IS NULL
                    AND mime LIKE 'image/%'
                    AND id > :id_offset
                    LIMIT :limit;",
                    ["id_offset" => $offset, "limit" => $limit]
                );
                $total = \count($files);
                $found = $this->findSources($files, [$this, "imageUpdate"]);

                if (PostSchedulingInfo::is_enabled()) {
                    /** @var array{array{id:int,filename:string}} $files  */
                    $files = Ctx::$database->get_all(
                        "SELECT * FROM scheduled_posts sp
                        LEFT JOIN scheduled_posts_metadata spm ON spm.schedule_id = sp.id AND spm.key = 'source'
                        WHERE spm.schedule_id IS NULL;"
                    );
                    $total += count($files);
                    $found += $this->findSources($files, [$this, "scheduleImageUpdate"]);
                }
                $exec_time = round(ftime() - $start_time, 2);
                $not = $total - $found;
                $message = "Found new sources for $found images and skipped $not images which did not seem like they came from inaturalist. Which took $exec_time seconds.";
                Log::info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    /**
     * @param array{array{id:int,filename:string}} $files
     */
    private function findSources(array $files, callable $func): int
    {
        $found = 0;
        foreach ($files as $file) {
            if (\Safe\preg_match(self::REGEX, \basename($file["filename"]), $matches)) {
                $source = $this->shapeSource($matches[1]);
                $func($file, $source);
                $found++;
            }
        }
        return $found;
    }

    private function shapeSource(int|string $observation_id): string
    {
        return "https://www.inaturalist.org/observations/$observation_id";
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
}
