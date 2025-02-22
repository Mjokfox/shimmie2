<?php

declare(strict_types=1);

namespace Shimmie2;

class UserCSSUserConfig extends UserConfigGroup
{
    public const KEY = "user_css";

    #[ConfigMeta("Custom CSS", ConfigType::STRING, input: "longtext")]
    public const CSS = 'user_css';
}
