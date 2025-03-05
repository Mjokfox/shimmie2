<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptchaInfo extends ExtensionInfo
{
    public const KEY = "site_captcha";

    public string $key = self::KEY;
    public string $name = "Site Captcha";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "sets up a site based captcha, which should have least user friction";
}
