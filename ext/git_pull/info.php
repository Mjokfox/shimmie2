<?php

declare(strict_types=1);

namespace Shimmie2;

class GitPullInfo extends ExtensionInfo
{
    public const KEY = "git_pull";

    public string $key = self::KEY;
    public string $name = "Git Pull";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Adds a git pull button to the admin panel";
    public bool $core = true;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
