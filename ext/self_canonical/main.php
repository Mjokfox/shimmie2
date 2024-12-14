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
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Self Canonical");
        $sb->add_text_option("self_domain", "The proper url that should be indexed: ");
        $sb->add_label('<br>&ensp;The url should look like this: " https://sub.domain.tld/ "');
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page,$config;
        $page->add_html_header(rawHTML(
           '<link rel="canonical" href="'.$config->get_string("self_domain").''.$event->path.'" >'
        ));
    }
}
