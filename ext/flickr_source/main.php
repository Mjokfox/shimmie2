<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B,TABLE,TR,TD,INPUT, rawHTML};

class FlickrSource extends Extension
{
    public function get_priority(): int
    {
        return 2;
    }
    // public function onImageInfoSet(ImageInfoSetEvent $event): void
    // {
    //     if (Extension::is_enabled(PostSourceInfo::KEY)){
    //         $slot = $event->slot;
    //         if(!($event->params["source"] || $event->params["source{$slot}"])){
    //             $image = $event->image;
    //             $filename = $image->filename;
    //             if(preg_match("/\d{9,12}_[a-f0-9]{8,12}_[a-z0-9]+(\.jpg|\.png)$/", $filename)){
    //                 $source = $this->getFlickrUrl(explode("_",$filename)[0]);
    //                 debug_log($source);
    //                 if ($source !== "https://flickr.com/photos///"){
    //                    send_event(new SourceSetEvent($image,$source));
    //                 }
    //             }
    //         }
    //     }

    // }
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $page, $database;
        $start_id = $database->get_one("SELECT max(id)-100 from images;");
        $html = (string)SHM_SIMPLE_FORM(
            "admin/flickr_source",
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
        $page->add_block(new Block("Flickr Source", rawHTML($html)));
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch ($event->action) {
            case "flickr_source":
                $start_time = ftime();
                $query = "SELECT *
                FROM images
                WHERE (source IS NULL OR source LIKE '%live.staticflickr%')
                AND mime LIKE 'image/%'
                AND id > :id_offset
                LIMIT :limit;";
                $files = $database->get_all($query, ["id_offset" => $event->params['id_offset'] | "0","limit" => $event->params['limit'] | "0"]);
                $i = 0;
                $j = [];
                $k = 0;
                foreach ($files as $file) {
                    if (!\safe\preg_match("/(\d{7,13})_[a-f0-9]{7,13}_[a-z0-9]{1,2}(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                        if (!\safe\preg_match("/[a-zA-Z\-]+_(\d{7,13})_o(?:_d)?(?:\.jpg|\.png)$/", $file["filename"], $matches)) {
                            $k++;
                            continue;
                        }
                    }
                    $source = $this->getFlickrUrl($matches[1]);
                    if ($source !== "https://flickr.com/photos///") {
                        $image = new Image($file);
                        send_event(new SourceSetEvent($image, $source));
                        $i++;
                    } else {
                        $j[] = $file["id"];
                    }
                }
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Found valid sources for {$i} images, invalid sources for ".count($j).", and skipped {$k} non flickr images, which took $exec_time seconds." . (count($j) > 0 ? " Failed: " . implode(", ", $j) : "");
                log_info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    public function getFlickrUrl(int|string $id): string
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
