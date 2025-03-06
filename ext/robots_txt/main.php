<?php

declare(strict_types=1);

namespace Shimmie2;

class RobotsBuildingEvent extends Event
{
    /** @var string[] */
    public array $parts = [
        "User-agent: *",
        // Site is rate limited to 1 request / sec,
        // returns 503 for more than that
    ];

    public function add_disallow(string $path): void
    {
        $this->parts[] = "Disallow: /$path";
    }
}

class RobotsTxt extends Extension
{
    public const KEY = "robots_txt";

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("robots.txt")) {
            $rbe = send_event(new RobotsBuildingEvent());
            $page->set_mode(PageMode::DATA);
            $page->set_mime("text/plain");
            $data = [
                $config->get_string(RobotsTxtConfig::ROBOTS_BEFORE),
                join("\n", $rbe->parts)
            ];
            $after = $config->get_string(RobotsTxtConfig::ROBOTS_AFTER);
            if ($after) {
                $data[] = $after;
            }
            $data[] = "Crawl-delay: " . $config->get_int(RobotsTxtConfig::ROBOTS_DELAY, 3);
            $page->set_data(join("\n", $data));
        }
    }


    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        global $config;
        $domain = $config->get_string(RobotsTxtConfig::CANONICAL_DOMAIN);
        if (!empty($domain) && $_SERVER['HTTP_HOST'] != $domain) {
            $event->add_disallow("");
        }
    }
}
