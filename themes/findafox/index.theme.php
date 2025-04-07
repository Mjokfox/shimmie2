<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, FORM, INPUT, LINK, META};

use MicroHTML\HTMLElement;

class CustomIndexTheme extends IndexTheme
{
    /**
     * @param Image[] $images
     */
    public function display_page(array $images): void
    {
        global $page;
        global $config, $user;
        $this->display_shortwiki();

        $this->display_page_header($images);
        $path = "list";
        if (\Safe\preg_match("/^\/post\/(list|search)\//", $_SERVER['REQUEST_URI'], $matches)) {
            $path = $matches[1];
        }
        if ($config->get(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
            $pos = "main";
            $id = "search-bar-right";
            $class = "full-width";
        } else {
            $pos = "left";
            $id = "search-bar";
            $class = "";
        }

        $nav = $this->build_navigation($this->page_number, $this->total_pages, ($path === "list" ? $this->search_terms : []), $class);

        $page->add_block(new Block("Search with tags", $nav, $pos, 2, $id));

        $page->add_block(new Block("Search with tags", $nav, "main", 5, "mobile-search"));

        $next = $this->page_number + 1;
        $prev = $this->page_number - 1;
        $query = implode(" ", $this->search_terms);

        if ($next <= $this->total_pages) {
            $page->add_html_header(LINK(["id" => "nextlink", "rel" => "next", "href" => make_link("post/$path".($query ? "/$query" : "")."/$next")]));
        }
        if ($prev > 0) {
            $page->add_html_header(LINK(["id" => "prevlink", "rel" => "previous", "href" => make_link("post/$path".($query ? "/$query" : "")."/$prev")]));
        }
        if (count($images) > 0) {
            $this->display_page_images($images);
        } else {
            throw new PostNotFound("No posts were found to match the search criteria");
        }
    }

    /**
     * @param string[] $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms, string $class = ""): HTMLElement
    {
        global $user;
        $action = search_link();
        return FORM(
            [
                "action" => $action,
                "method" => "GET",
                "class" => "search-bar $class"
            ],
            INPUT(["type" => "hidden", "name" => "q", "value" => $action->getPath()]),
            INPUT(["type" => "hidden", "name" => "auth_token", "value" => $user->get_auth_token()]),
            INPUT([
                "name" => 'search',
                "type" => 'text',
                "value" => Tag::implode($search_terms),
                "class" => 'autocomplete_tags',
                "placeholder" => 'tags'
            ]),
            SHM_SUBMIT("Go!"),
        );
    }

    /**
     * @param Image[] $images
     */
    protected function display_page_images(array $images): void
    {
        global $page;
        $path = "list";
        if (\Safe\preg_match("/^\/post\/(list|search)\//", $_SERVER['REQUEST_URI'], $matches)) {
            $path = $matches[1];
        }
        if (count($this->search_terms) > 0) {
            if ($this->page_number > 3) {
                // only index the first pages of each term
                $page->add_html_header(META(["name" => "robots", "content" => "noindex, nofollow"]));
            }
            $query = url_escape(Tag::implode($this->search_terms));
            $page->add_block(new Block("Posts ", $this->build_table($images, "search=$query"), "main", 10, "image-list"));
            $this->display_paginator("post/$path/$query", null, $this->page_number, $this->total_pages, true);
        } else {
            $page->add_block(new Block("Posts ", $this->build_table($images, null), "main", 10, "image-list"));
            $this->display_paginator("post/$path", null, $this->page_number, $this->total_pages, true);
        }
    }

    /**
     * @param Image[] $images
     */
    protected function build_table(array $images, ?string $query): HTMLElement
    {
        $table = DIV(["class" => "shm-image-list", "data-query" => $query]);
        foreach ($images as $image) {
            $table->appendChild($this->build_thumb($image));
        }
        return $table;
    }
}
