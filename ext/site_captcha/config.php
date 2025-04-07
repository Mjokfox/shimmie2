<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptchaConfig extends ConfigGroup
{
    public const KEY = "site_captcha";

    #[ConfigMeta("Whitelisted ips for captcha: ", ConfigType::STRING, default: "1.2.3.4,0102::1", input: ConfigInput::TEXTAREA, help:"comma separated, can be in CIDR format")]
    public const ALLOWED_IPS = "captcha_allowed_ips";
}
