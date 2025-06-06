<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<ImageViewCounterTheme> */
final class ImageViewCounter extends Extension
{
    public const KEY = "image_view_counter";
    public const VERSION_KEY = 'ext_image_view_counter';

    private int $view_interval = 3600; # allows views to be added each hour

    # Adds view to database if needed
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $database;

        $imgid = $event->image->id;

        // counts views from current IP in the last hour
        $recent_from_ip = (int)$database->get_one(
            "
				SELECT COUNT(*)
				FROM image_views
				WHERE ipaddress=:ipaddress AND timestamp >:lasthour AND image_id =:image_id
			",
            [
                "ipaddress" => Network::get_real_ip(),
                "lasthour" => time() - $this->view_interval,
                "image_id" => $imgid
            ]
        );

        // don't add view if person already viewed recently
        if ($recent_from_ip > 0) {
            return;
        }

        // Add view for current IP
        $database->execute(
            "
				INSERT INTO image_views (image_id, user_id, timestamp, ipaddress)
				VALUES (:image_id, :user_id, :timestamp, :ipaddress)
			",
            [
                "image_id" => $imgid,
                "user_id" => Ctx::$user->id,
                "timestamp" => time(),
                "ipaddress" => Network::get_real_ip(),
            ]
        );
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $database;

        if (Ctx::$user->can(ImageViewCounterPermission::SEE_IMAGE_VIEW_COUNTS)) {
            $view_count = (string)$database->get_one(
                "SELECT COUNT(*) FROM image_views WHERE image_id =:image_id",
                ["image_id" => $event->image->id]
            );

            $event->add_part(SHM_POST_INFO("Views", $view_count), 38);
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if (Ctx::$config->get("image_viewcounter_installed") !== null) {
            $this->set_version(1);
            Ctx::$config->delete("image_viewcounter_installed");
        }
        if ($this->get_version() < 1) {
            $database->create_table("image_views", "
                id SCORE_AIPK,
                image_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                timestamp INTEGER NOT NULL,
                ipaddress SCORE_INET NOT NULL");
            $this->set_version(1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;

        if ($event->page_matches("popular_images")) {
            $popular_ids = $database->get_col("
                SELECT image_id, count(*) AS total_views
                FROM image_views, images
                WHERE image_views.image_id = image_views.image_id
                AND image_views.image_id = images.id
                GROUP BY image_views.image_id
                ORDER BY total_views DESC
                LIMIT 100
            ");
            $images = Search::get_images($popular_ids);
            $this->theme->view_popular($images);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('popular_images'), "Popular Posts");
        }
    }
}
