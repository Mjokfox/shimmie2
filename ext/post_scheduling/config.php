<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSchedulingConfig extends ConfigGroup
{
    public const KEY = "post_scheduling";

    public const VERSION = "ext_post_scheduling_version";
    public const BASE = "scheduled_posts";

    #[ConfigMeta("Schedule interval", ConfigType::INT, default: 3600)]
    public const SCHEDULE_INTERVAL = "post_scheduling_interval";
    #[ConfigMeta("Schedule posting order", ConfigType::STRING, default: "id ASC")]
    public const SCHEDULE_ORDER = "post_scheduling_order";
}
