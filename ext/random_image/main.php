<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<RandomImageTheme> */
final class RandomImage extends Extension
{
    public const KEY = "random_image";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (
            $event->page_matches("random_image/{action}")
            || $event->page_matches("random_image/{action}/{search}")
        ) {
            $action = $event->get_arg('action');
            $search_terms = SearchTerm::explode($event->get_arg('search', ""));
            $image = Image::by_random($search_terms);
            if (!$image) {
                throw new PostNotFound("Couldn't find any posts randomly");
            }
            switch ($action) {
                case "download":
                    Ctx::$page->set_redirect($image->get_image_link());
                    break;
                case "post":
                    Ctx::$page->set_redirect(make_link("post/view/$image->id"));
                    break;
                case "view":
                    send_event(new DisplayingImageEvent($image));
                    break;
                case "thumb":
                    Ctx::$page->set_redirect($image->get_thumb_link());
                    break;
                case "widget":
                    Ctx::$page->set_data(MimeType::HTML, (string)$this->theme->build_thumb($image));
                    break;
                default:
                    send_event(new ImageDownloadingEvent($image, $image->get_image_filename(), $image->get_mime(), $event->GET));
            }
        }
    }

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        if (Ctx::$config->get(RandomImageConfig::SHOW_RANDOM_BLOCK)) {
            $image = Image::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($image);
            }
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('random_image/post'), "Random Post");
        }
    }

    #[EventListener]
    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // Its random so indexing it wont do any good
        $event->add_disallow("random_image");
    }
}
