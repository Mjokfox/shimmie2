<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexNow extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (!empty($config->get_string("indexnow_apikey"))) {
            $api_key = $config->get_string("indexnow_apikey");
            if (preg_match('/^[a-f0-9]{32}$/i', $api_key)) {
                if ($event->page_matches("$api_key.txt")) {
                    $page->set_mode(PageMode::DATA);
                    $page->set_mime(MimeType::TEXT);
                    $page->set_data($api_key);
                }
            }
        }
    }
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("IndexNow");
        $sb->add_text_option("indexnow_apikey", "api_key: ");
    }
}
