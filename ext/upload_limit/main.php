<?php

// declare(strict_types=1);

// namespace Shimmie2;

// use function MicroHTML\{rawHTML,emptyHTML,LABEL,BR};

// class UploadLimit extends DataHandlerExtension
// {
//     protected array $SUPPORTED_MIME = [MimeType::ZIP]; // actually unsupported since its inverted later on

//     public function get_priority(): int
//     {
//         return 1;
//     }

//     public function onInitExt(InitExtEvent $event): void
//     {
//         global $config;
//         $config->set_default_int("upload_refresh", 86400);
//         foreach (UserClass::$known_classes as $name => $value) {
//             if ($name == "hellbanned" || !$value->can(Permissions::CREATE_IMAGE) || $value->can(Permissions::BULK_IMPORT)) {
//                 continue;
//             }
//             $config->set_default_int("upload_limit:$name", 20);
//         }
//     }

//     public function onInitUserConfig(InitUserConfigEvent $event): void
//     {
//         global $config;
//         $event->user_config->set_default_int("last_upload", 0);
//         $event->user_config->set_default_int("left_upload", $config->get_int("upload_limit:".$event->user->class->name, 20));
//     }

//     public function onSetupBuilding(SetupBuildingEvent $event): void
//     {
//         $sb = $event->panel->create_new_block("Upload limiting");
//         $sb->add_int_option("upload_refresh", "upload refresh interval (seconds): ");

//         $sb->str_body .= "<table class='form' style='width:50%;transform: translate(50%,0%);'>";
//         $sb->start_table_head();
//         $sb->start_table_row();
//         $sb->str_body .= "<th><b>Class</b></th>";
//         $sb->str_body .= "<th><b>Upload limit</b></th>";
//         $sb->end_table_row();
//         $sb->end_table_head();
//         foreach (UserClass::$known_classes as $name => $value) {
//             if ($name == "hellbanned" || !$value->can(Permissions::CREATE_IMAGE) || $value->can(Permissions::BULK_IMPORT)) {
//                 continue;
//             }
//             $sb->start_table_row();
//             $sb->str_body .= "<td><b>$name</b></td>";
//             $sb->start_table_cell();
//             $sb->add_int_option("upload_limit:$name");
//             $sb->end_table_cell();
//             $sb->end_table_row();
//         }
//         $sb->end_table();
//         $sb->add_label("<br><b>Classes with BULK_IMPORT permission bypass any limit.</b>");
//     }

//     public function onUserPageBuilding(UserPageBuildingEvent $event): void
//     {
//         global $config, $page;
//         if ($event->display_user->can(Permissions::BULK_IMPORT)) {
//             $uploads_left = "∞ (admin)";
//             $formatted_time = "0";
//         } elseif ($event->display_user->can(Permissions::CREATE_IMAGE)) {
//             $duser_config = $event->display_user->get_config();
//             $uploads_left = $duser_config->get_int("left_upload");
//             $unixT = (int)date("U");
//             $deltaT = $unixT - $duser_config->get_int("last_upload");
//             $refresh_interval = $config->get_int("upload_refresh");
//             if ($deltaT > $refresh_interval) {
//                 $formatted_time = "0";
//             } else {
//                 $seconds = $refresh_interval - $deltaT;
//                 $hours = floor($seconds / 3600);
//                 $minutes = floor(($seconds % 3600) / 60);
//                 $remainingSeconds = $seconds % 60;
//                 $formatted_time = ($hours == 0 ? "" : ("$hours hour(s), ")) . ($minutes == 0 ? "" : ("$minutes minute(s), ")) . "$remainingSeconds second(s)";
//             }
//         } else {
//             $uploads_left = "0 (Cannot upload)";
//             $formatted_time = "∞";
//         }
//         $html = emptyHTML(
//             LABEL("Current amount of uploads left: $uploads_left"),
//             BR(),
//             LABEL("Time until refresh: $formatted_time")
//         );
//         $page->add_block(new Block("Upload limit", $html, "main", 20));
//     }

//     public function onDataUpload(DataUploadEvent $event): void
//     {
//         global $user;
//         if (!$this->supported_mime($event->mime)) {
//             global $config, $page;
//             $user_config = $user->get_config();
//             // code shamelessly stolen from extension.php, need to do this check to not decrement the counter on an otherwise failed upload
//             $existing = Image::by_hash(\Safe\md5_file($event->tmpname));
//             if (!is_null($existing)) {
//                 if ($config->get_string(ImageConfig::UPLOAD_COLLISION_HANDLER) == ImageConfig::COLLISION_MERGE) {
//                     // Right now tags are the only thing that get merged, so
//                     // we can just send a TagSetEvent - in the future we might
//                     // want a dedicated MergeEvent?
//                     if (!empty($event->metadata['tags'])) {
//                         $tags = Tag::explode($existing->get_tag_list() . " " . $event->metadata['tags']);
//                         send_event(new TagSetEvent($existing, $tags));
//                     }
//                     $event->images[] = $existing;
//                     return;
//                 } else {
//                     throw new UploadException(">>{$existing->id} already has hash {$existing->hash}");
//                 }
//             }
//             if (!$user->can(Permissions::BULK_IMPORT)) {
//                 $unixT = (int)date("U");
//                 $deltaT = $unixT - $user_config->get_int("last_upload");
//                 $refresh_interval = $config->get_int("upload_refresh");
//                 if ($deltaT > $refresh_interval) {
//                     $user_config->set_int("last_upload", $unixT);
//                     $name = $user->class->name;
//                     $uploads_left = $config->get_int("upload_limit:$name");
//                     $user_config->set_int("left_upload", $uploads_left);
//                 } else {
//                     $uploads_left = $user_config->get_int("left_upload");
//                 }
//                 if ($uploads_left < 1) {
//                     $seconds = $refresh_interval - $deltaT;
//                     $hours = floor($seconds / 3600);
//                     $minutes = floor(($seconds % 3600) / 60);
//                     $remainingSeconds = $seconds % 60;
//                     $formatted_time = ($hours == 0 ? "" : ("$hours hour(s), ")) . ($minutes == 0 ? "" : ("$minutes minute(s), ")) . "$remainingSeconds second(s)";
//                     throw new UploadException("Upload limit reached, please wait $formatted_time before trying again.");
//                 } else {
//                     $user_config->set_int("left_upload", $uploads_left - 1);
//                 }
//             }

//         }
//     }

//     public function onDisplayingImage(DisplayingImageEvent $event): void
//     {
//     }

//     // we don't actually do anything, just accept one upload and spawn several
//     protected function media_check_properties(MediaCheckPropertiesEvent $event): void
//     {
//     }

//     protected function check_contents(string $tmpname): bool
//     {
//         return false;
//     }

//     protected function create_thumb(Image $image): bool
//     {
//         return false;
//     }
// }
