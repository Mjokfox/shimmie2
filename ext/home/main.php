<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/** @extends Extension<HomeTheme> */
final class Home extends Extension
{
    public const KEY = "home";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("home")) {
            $this->theme->display_page(
                Ctx::$config->get(HomeConfig::TITLE) ?: Ctx::$config->get(SetupConfig::TITLE),
                $this->get_body()
            );
        }
    }

    private function get_body(): HTMLElement
    {
        // get the homelinks and process them
        if (!empty(Ctx::$config->get(HomeConfig::LINKS))) {
            $main_links = Ctx::$config->get(HomeConfig::LINKS);
        } else {
            $main_links = '[Posts](site://post/list)[Comments](site://comment/list)[Tags](site://tags)';
            if (PoolsInfo::is_enabled()) {
                $main_links .= '[Pools](site://pool/list)';
            }
            if (WikiInfo::is_enabled()) {
                $main_links .= '[Wiki](site://wiki)';
            }
            $main_links .= '[Documentation](site://ext_doc)';
        }

        return $this->theme->build_body(
            Ctx::$config->get(SetupConfig::TITLE),
            format_text($main_links),
            Ctx::$config->get(HomeConfig::TEXT),
            contact_link(),
            Search::count_images(),
        );
    }
}
