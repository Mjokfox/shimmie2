<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomReverseImageTheme extends ReverseImageTheme
{
    public function list_search(Page $page, string $search = ""): void
    {
        $nav = $this->build_navigation($search, "full-width");
        $page->add_block(new Block("Text Search", $nav, "main", 2, "text-search-right"));
        $page->add_block(new Block("Text Search", $nav, "main", 6, "text-mobile-search"));
    }

    public function view_search(Page $page, string $search = ""): void
    {
        $nav = $this->build_navigation($search, "");
        $page->add_block(new Block("Text Search", $nav, "left", 2, "text-search"));
        $page->add_block(new Block("Text Search", $nav, "main", 6, "text-mobile-search"));
    }
}
