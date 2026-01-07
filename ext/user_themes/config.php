<?php

declare(strict_types=1);

namespace Shimmie2;

class UserThemesUserConfig extends UserConfigGroup
{
    public const KEY = "user_themes";
    public ?string $title = "User theme";

    #[ConfigMeta("Website theme", ConfigType::STRING, options: "Shimmie2\UserThemes::get_user_themes")]
    public const THEME = 'user_theme';
}
