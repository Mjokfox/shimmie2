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

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Silly Cookies");
        $sb->add_text_option(SillyCookiesConfig::IMAGE_URL, "Image url: ");
        $sb->add_longtext_option(SillyCookiesConfig::COOKIES_TITLE, '<br>The title displayed above the image');
        $sb->add_longtext_option(SillyCookiesConfig::COOKIES_TEXT, '<br>The text to be displayed next to the image');
        $sb->add_bool_option(SillyCookiesConfig::GIB, "Add cookie dispenser?: ");
    }
}
