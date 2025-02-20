<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexNowConfig extends ConfigGroup
{
    public const KEY = "indexnow";

    #[ConfigMeta("API key", ConfigType::STRING)]
    public const APIKEY = 'indexnow_apikey';
}
