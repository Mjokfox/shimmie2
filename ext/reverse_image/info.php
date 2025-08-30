<?php

declare(strict_types=1);

namespace Shimmie2;

class ReverseImageInfo extends ExtensionInfo
{
    public const KEY = "reverse_image";

    public string $key = self::KEY;
    public string $name = "Reverse image";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Adds the ability to reverse image search on the site";
    public array $db_support = [DatabaseDriverID::PGSQL]; // requires cosine similarity (<=>)
}
