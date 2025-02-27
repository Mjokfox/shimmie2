<?php

declare(strict_types=1);

namespace Shimmie2;

class PostDescriptionInfo extends ExtensionInfo
{
    public const KEY = "post_description";

    public string $key = self::KEY;
    public string $name = "Post Description";
    public string $url = "findafox.net";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $description = "Adds a description to posts";
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
