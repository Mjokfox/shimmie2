<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexNow extends Extension
{
    public const KEY = "indexnow";
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (!empty($config->get_string(IndexNowConfig::APIKEY))) {
            $api_key = $config->get_string(IndexNowConfig::APIKEY);
            if (preg_match('/^[a-f0-9]{32}$/i', $api_key)) {
                if ($event->page_matches("$api_key.txt")) {
                    $page->set_mode(PageMode::DATA);
                    $page->set_mime(MimeType::TEXT);
                    $page->set_data($api_key);
                }
            }
        }
    }
}
