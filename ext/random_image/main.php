<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomImage extends Extension
{
    public const KEY = "random_image";
    /** @var RandomImageTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;

        if (
            $event->page_matches("random_image/{action}")
            || $event->page_matches("random_image/{action}/{search}")
        ) {
            $action = $event->get_arg('action');
            $search_terms = Tag::explode($event->get_arg('search', ""), false);
            $image = Image::by_random($search_terms);
            if (!$image) {
                throw new PostNotFound("Couldn't find any posts randomly");
            }
            switch ($action) {
                case "download":
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect($image->get_image_link());
                    break;
                case "view":
                    send_event(new DisplayingImageEvent($image));
                    break;
                case "thumb":
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect($image->get_thumb_link());
                    break;
                case "widget":
                    $page->set_mode(PageMode::DATA);
                    $page->set_mime(MimeType::HTML);
                    $page->set_data((string)$this->theme->build_thumb($image));
                    break;
                default:
                    $page->set_filename($image->filename, "inline");
                    send_event(new ImageDownloadingEvent($image, $image->get_image_filename(), $image->get_mime(), $event->GET));
            }
        }
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $config, $page;
        if ($config->get_bool(RandomImageConfig::SHOW_RANDOM_BLOCK)) {
            $image = Image::by_random($event->search_terms);
            if (!is_null($image)) {
                $this->theme->display_random($page, $image);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link(make_link('random_image/view'), "Random Post");
        }
    }

    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // Its random so indexing it wont do any good
        $event->add_disallow("random_image");
    }
}
