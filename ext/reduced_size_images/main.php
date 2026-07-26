<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};

final class ReducedSizeImages extends Extension
{
    public const KEY = "reduced_size_images";
    public const MEDIA_DIR = "reduced_images";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            Ctx::$database->execute("ALTER TABLE images ADD COLUMN has_reduced BOOLEAN NOT NULL DEFAULT FALSE");
            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        Post::$prop_types["has_reduced"] = PostPropType::BOOL;
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (\Safe\preg_match("/^_?images/i", $event->path)) {
            if (Ctx::$user->can(ReducedSizeImagesPermission::SEE_FULL_SIZE)) {
                Ctx::$page->set_data(MimeType::OCTET_STREAM, "");
            } else {
                throw new PermissionDenied("You're not allowed to view the full size image");
            }
            $event->stop_processing = true;
        }
    }

    #[EventListener(priority: 56)] // Needs to be after resize_image
    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->can_resize_mime($event->mime)) {
            $this->reduce_image($event->posts[0]);
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $all = Ctx::$database->get_one("SELECT count(id) FROM images");
        $has = Ctx::$database->get_one("SELECT count(id) FROM images WHERE has_reduced");
        $html = SHM_SIMPLE_FORM(
            make_link("admin/reduce_images"),
            INPUT(["type" => 'number', "name" => 'limit', "value" => "100", "style" => "width:5em"]),
            SHM_SUBMIT('Add reduced images'),
            " $has/$all"
        );
        Ctx::$page->add_block(new Block("Duplicate detector", $html));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "reduce_images":
                $start_time = ftime();
                $this->mass_reduce((int)$event->params['limit']);
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Made reduced copies for {$event->params['limit']} images, which took $exec_time seconds";
                Log::info("admin", $message, $message);
                break;
        }
    }

    #[EventListener(priority: 55)] // needs to be after replace_file so new_image is available
    public function onMediaReplace(MediaReplaceEvent $event): void
    {
        if (\is_null($event->new_image)) {
            return;
        }
        if ($this->can_resize_mime($event->new_image->get_mime())) {
            $this->reduce_image($event->new_image);
        }
        $old_path = Filesystem::warehouse_path(self::MEDIA_DIR, $event->old_hash);
        if ($old_path->exists()) {
            $old_path->unlink();
        }
    }

    #[EventListener(priority: 51)]
    public function onPostDeletion(PostDeletionEvent $event): void
    {
        $path = Filesystem::warehouse_path(self::MEDIA_DIR, $event->image->hash);
        if ($path->exists()) {
            $path->unlink();
        }
    }

    private function mass_reduce(int $limit): void
    {
        $rows = Ctx::$database->get_all(
            "SELECT id, hash, width, height, mime FROM images 
            WHERE NOT has_reduced
            ORDER BY id ASC LIMIT :limit",
            ["limit" => $limit]
        );
        foreach ($rows as $row) {
            $post = new Post($row);
            if (!Filesystem::warehouse_path(self::MEDIA_DIR, $post->hash, false)->exists()) {
                if ($this->can_resize_mime($post->get_mime())) {
                    $this->reduce_image($post);
                }
            }
            Ctx::$database->execute("UPDATE images SET has_reduced = TRUE WHERE id = :id", ["id" => $post->id]);
        }
    }

    private function reduce_image(Post $post): void
    {
        $default_scale = Ctx::$config->get(ReducedSizeImagesConfig::RESIZE_PERCENTAGE) / 100;
        $min_width = Ctx::$config->get(ReducedSizeImagesConfig::MINIMUM_WIDTH);
        $min_height = Ctx::$config->get(ReducedSizeImagesConfig::MINIMUM_HEIGHT);

        $scale = max($default_scale, $min_width / $post->width, $min_height / $post->height);
        if ($scale > 1 || $scale <= 0) { // no need to resize
            return;
        }

        $width = (int)ceil($post->width * $scale);
        $height = (int)ceil($post->height * $scale);

        $isanigif = 0;
        if ($post->get_mime()->base === MimeType::GIF) {
            $image_filename = Filesystem::warehouse_path(Post::MEDIA_DIR, $post->hash);
            $fh = \Safe\fopen($image_filename->str(), 'rb');
            //check if gif is animated (via https://www.php.net/manual/en/function.imagecreatefromgif.php#104473)
            while (!feof($fh) && $isanigif < 2) {
                $chunk = \Safe\fread($fh, 1024 * 100);
                $isanigif += \Safe\preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk);
            }
        }
        if ($isanigif === 0) {
            try {
                $this->resize_image($post, $width, $height);
                Log::info("reduze_size", ">>{$post->id} has been given a reduced image to: ".$width."x".$height);
            } catch (MediaException $e) {
                // dont care, this is thrown for AVIF for some reason, even though we check if its supported twice
            }
        }
    }

    private function can_resize_mime(MimeType $mime): bool
    {
        $engine = MediaEngine::from(Ctx::$config->get(ResizeConfig::ENGINE));
        return MediaEngine::is_input_supported($engine, $mime)
                && MediaEngine::is_output_supported($engine, $mime);
    }

    private function resize_image(Post $image_obj, int $width, int $height): void
    {
        if (($height <= 0) || ($width <= 0)) {
            throw new ImageResizeException("Invalid options for height and width. ($width x $height)");
        }

        $engine = MediaEngine::from(Ctx::$config->get(ResizeConfig::ENGINE));

        if (!$this->can_resize_mime($image_obj->get_mime())) {
            throw new ImageResizeException("Engine {$engine->value} cannot resize selected image");
        }

        $hash = $image_obj->hash;
        $image_filename = Filesystem::warehouse_path(Post::MEDIA_DIR, $hash);

        /* Temp storage while we resize */
        $outname = Filesystem::warehouse_path(self::MEDIA_DIR, $hash);

        send_event(new MediaResizeEvent(
            $engine,
            $image_filename,
            $image_obj->get_mime(),
            $outname,
            $width,
            $height,
            ResizeType::STRETCH
        ));

        Log::info("resize", "Resized >>{$image_obj->id} - New hash: {$image_obj->hash}");
    }
}
