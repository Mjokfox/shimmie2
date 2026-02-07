<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{STYLE,rawHTML};

class UserCSS extends Extension
{
    public const KEY = "user_css";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        Ctx::$page->add_html_header(STYLE(
            rawHTML(htmlentities(Ctx::$user->get_config()->get(UserCSSUserConfig::CSS) ?? "", ENT_NOQUOTES, "UTF-8"))
        ));
    }
}
