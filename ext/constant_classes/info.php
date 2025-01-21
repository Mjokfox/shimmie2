<?php

declare(strict_types=1);

namespace Shimmie2;

class ConstantClassesInfo extends ExtensionInfo
{
    public const KEY = "constant_classes";

    public string $key = self::KEY;
    public string $name = "constant classes";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public bool $core = true;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds just a moderator class for now without the use of the data dir, which isnt stored on the git";
}