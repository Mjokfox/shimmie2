<?php

declare(strict_types=1);

namespace Shimmie2;

class EmailVerificationInfo extends ExtensionInfo
{
    public const KEY = "email_verification";

    public string $key = self::KEY;
    public string $name = "Email Verification";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Adds email verification";
    public array $dependencies = [PermManagerInfo::KEY];
    public bool $core = false;
}
