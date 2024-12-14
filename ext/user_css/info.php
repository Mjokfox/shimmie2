<?php

declare(strict_types=1);

namespace Shimmie2;

class UserCSSInfo extends ExtensionInfo
{
    public const KEY = "user_css";

    public string $key = self::KEY;
    public string $name = "User-specific CSS";
    public array $authors = ["Mjokfox" => "mjokfox@hotmail.com"];
    public string $license = "GNU GPLv3";
    public string $description = "Allow users to set their own styling.";
}
