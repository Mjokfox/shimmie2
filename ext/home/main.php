<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class Home extends Extension
{
    public const KEY = "home";
    /** @var HomeTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if ($event->page_matches("home")) {
            $this->theme->display_page(
                $page,
                $config->get_string(HomeConfig::TITLE) ?: $config->get_string(SetupConfig::TITLE),
                $this->get_body()
            );
        }
    }

    private function get_body(): HTMLElement
    {
        // returns just the contents of the body
        global $config;

        // get the homelinks and process them
        if (strlen($config->get_string(HomeConfig::LINKS, '')) > 0) {
            $main_links = $config->get_string(HomeConfig::LINKS);
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
            $config->get_string(SetupConfig::TITLE),
            format_text($main_links),
            $config->get_string(HomeConfig::TEXT, null),
            contact_link(),
            Search::count_images(),
        );
    }
}
