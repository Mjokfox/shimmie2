<?php

declare(strict_types=1);

namespace Shimmie2;

class PostDescriptionConfig extends ConfigGroup
{
    public const KEY = "post_description";

    #[ConfigMeta("version", ConfigType::INT, advanced:true)]
    public const VERSION = "ext_post_description_version";
}
