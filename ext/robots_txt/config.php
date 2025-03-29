<?php

declare(strict_types=1);

namespace Shimmie2;

final class RobotsTxtConfig extends ConfigGroup
{
    public const KEY = "robots_txt";

    #[ConfigMeta("Canonical domain", ConfigType::STRING, default: null, advanced: true, help: "If set, requests to this site via other domains will be blocked")]
    public const CANONICAL_DOMAIN = "robots_txt_canonical_domain";

    #[ConfigMeta("Text to add before the main user-agent *", ConfigType::STRING, input: ConfigInput::TEXTAREA)]
    public const ROBOTS_BEFORE = "robots_txt_bef";
    #[ConfigMeta("Text to add after the main user-agent *", ConfigType::STRING, input: ConfigInput::TEXTAREA)]
    public const ROBOTS_AFTER = "robots_txt_aft";
    #[ConfigMeta("* Crawl-delay: ", ConfigType::INT, default: 3)]
    public const ROBOTS_DELAY = "robots_txt_delay";
}
