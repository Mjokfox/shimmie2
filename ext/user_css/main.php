<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{STYLE};

class UserCSS extends Extension
{
    /** @var UserCSSTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page,$user_config;
        $page->add_html_header(STYLE(
            $user_config->get_string("user_css")
        ));
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("CSS");
        $sb->add_longtext_option("user_css", 'User defined styling');
        $sb->add_label("This controls the styling to be added on every page on this site.");
    }
}

