<?php

declare(strict_types=1);

namespace Shimmie2;

class StaticFilesConfig extends ConfigGroup
{
    public const KEY = "static_files";

    #[ConfigMeta("Text to add before the main user-agent *", ConfigType::STRING, input: "longtext")]
    public const ROBOTS_BEFORE = "robots_txt_bef";
    #[ConfigMeta("Text to add after the main user-agent *", ConfigType::STRING, input: "longtext")]
    public const ROBOTS_AFTER = "robots_txt_aft";
    #[ConfigMeta("* Crawl-delay: ", ConfigType::INT, default: 3)]
    public const ROBOTS_DELAY = "robots_txt_delay";
}
