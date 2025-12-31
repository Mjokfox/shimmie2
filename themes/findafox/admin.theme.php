<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomAdminPageTheme extends AdminPageTheme
{
    public function display_page(): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_page();
    }
}
