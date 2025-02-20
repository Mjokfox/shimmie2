<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

require_once "config.php";
// https://openclipart.org/detail/314331/chocolate-chip-cookie
class SillyCookies extends Extension
{
    public function get_priority(): int
    {
        return 49;
    }
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        $config->set_default_string(SillyCookiesConfig::IMAGE_URL, "");
        $config->set_default_string(SillyCookiesConfig::COOKIES_TITLE, "");
        $config->set_default_string(SillyCookiesConfig::COOKIES_TEXT, "");
        $config->set_default_bool(SillyCookiesConfig::GIB, true);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $config;
        if ($event->page_matches("home")) {
            $url = html_escape($config->get_string(SillyCookiesConfig::IMAGE_URL));
            $title = html_escape($config->get_string(SillyCookiesConfig::COOKIES_TITLE));
            $text = html_escape($config->get_string(SillyCookiesConfig::COOKIES_TEXT));
            $gib = $config->get_bool(SillyCookiesConfig::GIB) ? "true" : "false";
            $page->add_html_header(rawHTML("<script>window.silly_cookies_url = '$url';window.silly_cookies_title = '$title'; window.silly_cookies_text = '$text'; window.silly_cookies_gib = $gib;</script>"));
        }
    }
}
