<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, ARTICLE, BODY, BR, DIV, FOOTER, H1, HEADER, IMG, LI, LINK, META, NAV, SCRIPT, UL, emptyHTML, joinHTML, rawHTML};

use MicroHTML\HTMLElement;

class customPage extends Page
{
    use Page_Page;

    protected function body_html(): HTMLElement
    {
        list($nav_links, $sub_links) = $this->get_nav_links();

        $left_block_html = [];
        $user_block_html = [];
        $main_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, false);
                    break;
                case "user":
                    $user_block_html[] = $block->body;
                    break;
                case "subheading":
                    $sub_block_html[] = $block->body;
                    break;
                case "main":
                    if ($block->header === "Posts") {
                        $block->header = "&nbsp;";
                    }
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        if ($this->subheading === "") {
            $subheading = null;
        } else {
            $subheading = DIV(["id" => "subtitle"], $this->subheading);
        }

        $site_name = Ctx::$config->get(SetupConfig::TITLE); // bzchan: change from normal default to get title for top of page
        $main_page = Ctx::$config->get(SetupConfig::MAIN_PAGE); // bzchan: change from normal default to get main page for top of page

        $custom_links = emptyHTML();
        foreach ($nav_links as $nav_link) {
            $custom_links->appendChild(LI($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)));
        }

        $custom_sublinks = "";
        if (count($sub_links) > 0) {
            $custom_sublinks = DIV(["class" => "sbar"]);
            foreach ($sub_links as $nav_link) {
                $custom_sublinks->appendChild(LI($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)));
            }
        }

        $title_link = H1(
            ["id" => "site-title"],
            A(
                ["href" => make_link($main_page)],
                IMG(["src" => "/web-app-manifest-192x192.png", "alt" => "", "class" => "logo"]),
                $site_name
            )
        );
        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return BODY(
            $this->body_attrs(),
            HEADER(
                emptyHTML(
                    DIV(
                        ["class" => "title-container"],
                        $title_link,
                        DIV(
                            ["class" => "mobile-burger",],
                            A([
                                "onclick" => '$(".flat-list").toggle();$(this).text($(this).text() === "≡" ? "×" : "≡");'
                            ], "≡")
                        )
                    ),
                    UL(["id" => "navbar", "class" => "flat-list"], $custom_links),
                    UL(["id" => "subnavbar", "class" => "flat-list"], $custom_sublinks),
                )
            ),
            $subheading,
            emptyHTML(...$sub_block_html),
            NAV(...$left_block_html),
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER(DIV($footer_html))
        );
    }

    private function navlinks(Url $link, HTMLElement|string $desc, bool $active): HTMLElement
    {
        return A([
            "class" => $active ? "current-page" : "tab",
            "href" => $link,
        ], $desc);
    }

    protected function footer_html(): HTMLElement
    {
        $debug = get_debug_info();
        $contact_link = contact_link() ?? "";
        $footer_html = Ctx::$config->get(SetupConfig::FOOTER_HTML);
        if (!empty($footer_html)) {
            $footer_html = str_replace('%d', $debug, $footer_html);
            $footer_html = str_replace('%c', $contact_link, $footer_html);
            /** @var string $footer_html */
            return rawHTML($footer_html);
        }
        return joinHTML("", [
            "Media © their respective owners, ",
            A(["href" => "https://code.shishnet.org/shimmie2/", "title" => $debug], "Shimmie"),
            " © ",
            A(["href" => "https://www.shishnet.org/"], "Shish"),
            " & ",
            A(["href" => "https://github.com/shish/shimmie2/graphs/contributors"], "The Team"),
            " 2007-2024, based on the Danbooru concept.",
            $contact_link ? emptyHTML(BR(), A(["href" => $contact_link], "Contact")) : ""
        ]);
    }

    public function add_auto_html_headers(): void
    {
        $data_href = (string)Url::base();
        $theme_name = get_theme();

        $this->add_html_header(META([
            "name" => "viewport",
            "content" => "width=device-width,initial-scale=1"
        ]), 40);

        # static handler will map these to themes/foo/static/bar.ico or ext/static_files/static/bar.ico
        $this->add_html_header(LINK([
            'rel' => 'icon', 'type' => 'image/png',
            'href' => "$data_href/favicon-48x48.png",
            'sizes' => '48x48'
        ]), 41);
        $this->add_html_header(LINK([
            'rel' => 'icon', 'type' => 'image/svg+xml',
            'href' => "$data_href/favicon.svg"
        ]), 42);
        $this->add_html_header(LINK([
            'rel' => 'shortcut icon',
            'href' => "$data_href/favicon.ico"
        ]), 42);
        $this->add_html_header(LINK([
            'rel' => 'apple-touch-icon',
            'sizes' => '180x180',
            'href' => "$data_href/apple-touch-icon.png"
        ]), 42);
        $this->add_html_header(META([
            'name' => 'apple-mobile-web-app-title',
            'content' => 'FindaFox'
        ]), 42);
        $this->add_html_header(LINK([
            'rel' => 'manifest',
            'href' => "$data_href/site.webmanifest"
        ]), 42);

        //We use $config_latest to make sure cache is reset if config is ever updated.
        $config_latest = 0;
        foreach (Filesystem::zglob("data/config/*") as $conf) {
            $config_latest = max($config_latest, $conf->filemtime());
        }

        $css_cache_file = $this->get_css_cache_file($theme_name, $config_latest);
        $this->add_html_header(LINK([
            'rel' => 'stylesheet',
            'href' => "$data_href/{$css_cache_file->str()}",
            'type' => 'text/css'
        ]), 43);

        $initjs_cache_file = $this->get_initjs_cache_file($theme_name, $config_latest);
        $this->add_html_header(SCRIPT([
            'src' => "$data_href/{$initjs_cache_file->str()}",
            'type' => 'text/javascript'
        ]));

        $js_cache_file = $this->get_js_cache_file($theme_name, $config_latest);
        $this->add_html_header(SCRIPT([
            'defer' => true,
            'src' => "$data_href/{$js_cache_file->str()}",
            'type' => 'text/javascript'
        ]));
    }
}
