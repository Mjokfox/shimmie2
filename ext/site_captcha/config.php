<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptchaConfig extends ConfigGroup
{
    public const KEY = "site_captcha";

    #[ConfigMeta("Whitelisted ips for captcha: ", ConfigType::STRING, input: "longtext", help:"comma separated, can be in CIDR format")]
    public const ALLOWED_IPS = "captcha_allowed_ips";
}
