<?php

declare(strict_types=1);

namespace Shimmie2;

class ReverseSearchLinksConfig extends ConfigGroup
{
    public const KEY = "reverse_search_links";

    #[ConfigMeta("Enabled services", ConfigType::ARRAY, options: [
        'Google' => 'Google',
        'SauceNAO' => 'SauceNAO',
        'TinEye' => 'TinEye',
        'trace.moe' => 'trace.moe',
        'ascii2d' => 'ascii2d',
        'Yandex' => 'Yandex',
    ], default: ['google', 'SauceNAO', 'TinEye', 'trace.moe', 'ascii2d', 'Yandex'], advanced: true)]
    public const ENABLED_SERVICES = "ext_reverse_search_links_enabled_services";
}
