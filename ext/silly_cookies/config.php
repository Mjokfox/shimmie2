<?php

declare(strict_types=1);

namespace Shimmie2;

class SillyCookiesConfig extends ConfigGroup
{
    public const KEY = "silly_cookies";

    public const VERSION = "ext_silly_cookies_version";

    #[ConfigMeta("Image url: ", ConfigType::STRING)]
    public const IMAGE_URL = "silly_cookies_image_url";

    #[ConfigMeta("The title above", ConfigType::STRING, default: "Shimmie", input: ConfigInput::TEXTAREA)]
    public const COOKIES_TITLE = "silly_cookies_title";

    #[ConfigMeta("The text to the right", ConfigType::STRING, default: "Shimmie", input: ConfigInput::TEXTAREA)]
    public const COOKIES_TEXT = "silly_cookies_text";

    #[ConfigMeta("Add cookie dispenser?", ConfigType::BOOL, default: true)]
    public const GIB = "silly_cookies_give";
}
