<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{STYLE,rawHTML};

class UserCSS extends Extension
{
    public const KEY = "user_css";
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page,$user;
        $page->add_html_header(STYLE(
            rawHTML(htmlentities($user->get_config()->get_string(UserCSSUserConfig::CSS) ?? "", ENT_NOQUOTES, "UTF-8"))
        ));
    }
}
