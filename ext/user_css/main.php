<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{STYLE};

class UserCSS extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page,$user;
        $page->add_html_header(STYLE(
            $user->get_config()->get_string(UserCSSUserConfig::CSS)
        ));
    }
}
