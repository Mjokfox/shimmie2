<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptchaConfig extends ConfigGroup
{
    public const KEY = "site_captcha";

    #[ConfigMeta("Whitelisted ips: ", ConfigType::STRING, default: "127.0.0.1/24,0102::1", input: ConfigInput::TEXTAREA, help:"comma separated, can be in CIDR format")]
    public const ALLOWED_IPS = "captcha_allowed_ips";
    #[ConfigMeta("Whitelisted user agents: ", ConfigType::STRING, default: "", input: ConfigInput::TEXTAREA, help:"comma separated, user agents can be spoofed, use at your own risk")]
    public const ALLOWED_USERAGENTS = "captcha_allowed_uas";
}
