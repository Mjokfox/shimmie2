<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexNowInfo extends ExtensionInfo
{
    public const KEY = "indexnow";

    public string $key = self::KEY;
    public string $name = "IndexNow";
    public string $url = "http://findafox.com";
    public array $authors = ["mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "IndexNow integration for faster site crawling";
}
