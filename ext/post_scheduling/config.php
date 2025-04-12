<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSchedulingConfig extends ConfigGroup
{
    public const KEY = "post_scheduling";

    public const VERSION = "ext_post_scheduling_version";

    #[ConfigMeta("Schedule interval", ConfigType::INT, default: 3600)]
    public const SCHEDULE_INTERVAL = "post_scheduling_interval";
}
