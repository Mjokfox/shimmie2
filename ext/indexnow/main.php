<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexNow extends Extension
{
    public const KEY = "indexnow";
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (!empty(Ctx::$config->get(IndexNowConfig::APIKEY))) {
            $api_key = Ctx::$config->get(IndexNowConfig::APIKEY);
            if (preg_match('/^[a-f0-9]{32}$/i', $api_key)) {
                if ($event->page_matches("$api_key.txt")) {
                    Ctx::$page->set_data(MimeType::TEXT, $api_key);
                }
            }
        }
    }
}
