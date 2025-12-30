<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotificationsInfo extends ExtensionInfo
{
    public const KEY = "notifications";

    public string $name = "Notifications";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Receive a notification from interactions on your posts and comments";
    public ?string $documentation =
        "A notification is given in the navbar when someone makes a comment on a post you uploaded, mentions you in a comment or forum post or replies to such.";
}
