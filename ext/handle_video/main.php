<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_video";
    public const SUPPORTED_MIME = [
        MimeType::ASF,
        MimeType::AVI,
        MimeType::FLASH_VIDEO,
        MimeType::MKV,
        MimeType::MP4_VIDEO,
        MimeType::OGG_VIDEO,
        MimeType::QUICKTIME,
        MimeType::WEBM,
    ];

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->video = true;
        $event->image->image = false;
        try {
            $data = Media::get_ffprobe_data($event->image->get_image_filename()->str());

            if (array_key_exists("streams", $data)) {
                $video = false;
                $audio = false;
                $video_codec = null;
                $streams = $data["streams"];
                if (is_array($streams)) {
                    foreach ($streams as $stream) {
                        if (is_array($stream)) {
                            if (array_key_exists("codec_type", $stream)) {
                                $type = $stream["codec_type"];
                                switch ($type) {
                                    case "audio":
                                        $audio = true;
                                        break;
                                    case "video":
                                        $video = true;
                                        $video_codec = $stream["codec_name"];
                                        break;
                                }
                            }
                            $event->image->width = max($event->image->width, @$stream["width"]);
                            $event->image->height = max($event->image->height, @$stream["height"]);
                        }
                    }
                    $event->image->video = $video;
                    $event->image->video_codec = $video_codec;
                    $event->image->audio = $audio;
                    if ($event->image->get_mime()->base === MimeType::MKV &&
                        $event->image->video_codec !== null &&
                        VideoContainer::is_video_codec_supported(VideoContainer::WEBM, $event->image->video_codec)) {
                        // WEBMs are MKVs with the VP9 or VP8 codec
                        // For browser-friendliness, we'll just change the mime type
                        $event->image->set_mime(MimeType::WEBM);
                    }
                }
            }
            if (array_key_exists("format", $data) && is_array($data["format"])) {
                $format = $data["format"];
                if (array_key_exists("duration", $format) && is_numeric($format["duration"])) {
                    $event->image->length = (int)floor(floatval($format["duration"]) * 1000);
                }
            }
        } catch (MediaException $e) {
            // a post with no metadata is better than no post
        }
    }

    protected function supported_mime(MimeType $mime): bool
    {
        $enabled_formats = Ctx::$config->req(VideoFileHandlerConfig::ENABLED_FORMATS);
        return MimeType::matches_array($mime, $enabled_formats, true);
    }

    protected function create_thumb(Image $image): bool
    {
        return Media::create_thumbnail_ffmpeg($image);
    }

    protected function check_contents(Path $tmpname): bool
    {
        if ($tmpname->exists()) {
            $mime = MimeType::get_for_file($tmpname);

            $enabled_formats = Ctx::$config->req(VideoFileHandlerConfig::ENABLED_FORMATS);
            if (MimeType::matches_array($mime, $enabled_formats)) {
                return true;
            }
        }
        return false;
    }
}
