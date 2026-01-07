<?php

declare(strict_types=1);

namespace Shimmie2;

class UserThemesInfo extends ExtensionInfo
{
    public const KEY = "user_themes";

    public string $key = self::KEY;
    public string $name = "User Themes";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = "GNU GPLv3";
    public string $description = "Lets admins upload themes, for all users to use!";
}
