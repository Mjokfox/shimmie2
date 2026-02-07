<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class SelfCanonical extends Extension
{
    public const KEY = "self_canonical";
    public function get_priority(): int
    {
        return 1;
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        Ctx::$page->add_html_header(rawHTML(
            '<link rel="canonical" href="'.Ctx::$config->get(SelfCanonicalConfig::DOMAIN).''.$event->path.'" >'
        ));
    }
}
