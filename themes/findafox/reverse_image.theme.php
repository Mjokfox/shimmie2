<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{rawHTML};

class CustomReverseImageTheme extends ReverseImageTheme
{
    public function list_search(string $search = ""): void
    {
        global $page;
        $nav = $this->build_navigation($search, "full-width");
        $page->add_block(new Block("Or describe an image", $nav, "main", 2, "text-search-right"));
        $page->add_block(new Block("Or describe an image", $nav, "main", 6, "text-mobile-search"));
    }

    public function view_search(string $search = ""): void
    {
        global $page;
        $nav = $this->build_navigation($search, "");
        $page->add_block(new Block("Or describe an image", $nav, "left", 2, "text-search"));
        $page->add_block(new Block("Or describe an image", $nav, "main", 6, "text-mobile-search"));
    }

    public function build_navigation(string $search_string = "", string $class = ""): HTMLElement
    {
        $h_search_link = make_link("post/search/1");
        return rawHTML("
			<form action='$h_search_link' method='GET' class='search-bar $class'>
				<input name='search' type='text' value='$search_string' class='text-search' placeholder='text description'/>
				<input type='submit' value='Go!'>
				<input type='hidden' name='q' value='post/list'>
			</form>
		");
    }
}
