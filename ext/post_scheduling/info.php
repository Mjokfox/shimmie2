<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSchedulingInfo extends ExtensionInfo
{
    public const KEY = "post_scheduling";

    public string $key = self::KEY;
    public string $name = "Post Scheduling";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Let users schedule a post upload when no new post has been uploaded in a given time.";
    public ?string $documentation =
        "You might want to add a systemd or other script on startup to check if there are images in the queue still.
        So for example: `php ext/post_scheduling/timer.php 1`";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
