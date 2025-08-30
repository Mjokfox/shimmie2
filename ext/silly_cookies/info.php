<?php

declare(strict_types=1);

namespace Shimmie2;

class SillyCookiesInfo extends ExtensionInfo
{
    public const KEY = "silly_cookies";

    public string $key = self::KEY;
    public string $name = "Silly cookies";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "adds a silly cookies footer on the home page";
    public array $dependencies = [HomeInfo::KEY];
}
