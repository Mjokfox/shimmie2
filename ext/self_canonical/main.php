<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class SelfCanonical extends Extension
{
    public function get_priority(): int
    {
        return 1;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page,$config;
        $page->add_html_header(rawHTML(
            '<link rel="canonical" href="'.$config->get_string(SelfCanonicalConfig::DOMAIN).''.$event->path.'" >'
        ));
    }
}
