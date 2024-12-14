<?php

declare(strict_types=1);

namespace Shimmie2;

class FlickrSourceInfo extends ExtensionInfo
{
    public const KEY = "flickr_source";

    public string $key = self::KEY;
    public string $name = "Flickr Source";
    public array $authors = ["Mjokfox"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "automatically adds the flickr source whent the filename matches a flickr shape";
}
