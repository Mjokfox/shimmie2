<?php

declare(strict_types=1);

namespace Shimmie2;

class SelfCanonicalInfo extends ExtensionInfo
{
    public const KEY = "self_canonical";

    public string $key = self::KEY;
    public string $name = "Self Canonical";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Adds a canonical link to every page";
}
