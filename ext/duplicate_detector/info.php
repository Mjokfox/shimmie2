<?php

declare(strict_types=1);

namespace Shimmie2;

class DuplicateDetectorInfo extends ExtensionInfo
{
    public const KEY = "duplicate_detector";

    public string $key = self::KEY;
    public string $name = "Duplicate detector";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Automatically find duplicates";
    public array $db_support = [DatabaseDriverID::PGSQL]; // probably
}
