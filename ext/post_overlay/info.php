<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostOverlayInfo extends ExtensionInfo
{
    public const KEY = "post_overlay";

    public string $name = "Post Overlay";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Overlays post score, favorite, comment count etc. over thumbnails.";
}
