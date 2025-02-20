<?php

declare(strict_types=1);

namespace Shimmie2;

class DmcaConfig extends ConfigGroup
{
    public const KEY = "dmca";

    #[ConfigMeta("Admin email", ConfigType::STRING)]
    public const EMAIL = "dmca_emal";
}
