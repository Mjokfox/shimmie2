<?php

declare(strict_types=1);

namespace Shimmie2;

class SelfCanonicalConfig extends ConfigGroup
{
    public const KEY = "self_canonical";

    #[ConfigMeta("The proper domain", ConfigType::STRING)]
    public const DOMAIN = "self_domain";

}
