<?php

declare(strict_types=1);

namespace Shimmie2;

class DmcaInfo extends ExtensionInfo
{
    public const KEY = "dmca";

    public string $key = self::KEY;
    public string $name = "Dmca";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds a page where DMCA takedown request can be made (HARDCODED BITS CURRENTLY)";
}
