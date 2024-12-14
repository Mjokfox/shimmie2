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
        global $page;
        $html = (string)SHM_SIMPLE_FORM(
            "admin/flickr_source",
            TABLE( 
            TR(
                TD(["style" => "padding-right:5px"],B("Start id")),TD(INPUT(["type" => 'number', "name" => 'flickr_start_id', "value" => "0", "style" => "width:5em"])),
            ),
            TR(
                TD(B("Limit")),TD(INPUT(["type" => 'number', "name" => 'flickr_limit', "value" => "100", "style" => "width:5em"])),
            ),
        ),
            SHM_SUBMIT('Find all flickr sources'),
            
        );
        $page->add_block(new Block("Flickr Source", rawHTML($html)));
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch($event->action) {
            case "flickr_source":
                $start_time = ftime();
                $query = "SELECT id, filename
                FROM images
                WHERE source IS NULL 
                AND mime LIKE 'image/%'
                AND id > :id
                LIMIT :limit;";
                $files = $database->get_all($query,["id" => $event->params['flickr_start_id'] | "0","limit" => $event->params['flickr_limit'] | "0"]);
                $i = 0;
                $j = 0;
                $k = 0;
                foreach ($files as $file) {
                    if(preg_match("/\d{9,12}_[a-f0-9]{8,12}_[a-z0-9]+(\.jpg|\.png)$/", $file["filename"])){
                        $source = $this->getFlickrUrl(explode("_",$file["filename"])[0]);
                        if ($source !== "https://flickr.com/photos///"){
                            send_event(new SourceSetEvent(Image::by_id($file["id"]),$source));
                            $i++;
                        }
                        else{
                            $j++;
                        }
                    } else {
                        $k++;
                    }
                }
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Found valid sources for {$i} images, invalid sources for {$j}, and skipped {$k} non flickr images, which took $exec_time seconds.";
                log_info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    public function getFlickrUrl($id): string {
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
