<?php

declare(strict_types=1);

namespace Shimmie2;

class UserCSSUserConfig extends UserConfigGroup
{
    public const KEY = "user_css";
    public ?string $title = "User CSS";

    #[ConfigMeta("Custom CSS", ConfigType::STRING, input: ConfigInput::TEXTAREA)]
    public const CSS = 'user_css';
}
