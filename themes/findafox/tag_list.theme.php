<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomTagListTheme extends TagListTheme
{
    public function display_page(Page $page): void
    {
        $page->set_layout("no-left");
        parent::display_page($page);
    }
}
