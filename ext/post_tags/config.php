<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTagsConfig extends ConfigGroup
{
    public const KEY = "post_tags";

    #[ConfigMeta(
        "Force tags to lowercase",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "This does not change existing tags. Use the Board Admin tool to set all existing tags to lowercase."
    )]
    public const FORCE_LOWERCASE = "post_tags_force_lowercase";

    #[ConfigMeta(
        "Forbidden characters",
        ConfigType::STRING,
        default: "",
        advanced: true,
        help: "This does not change existing tags. Use the Board Admin tool to manually edit existing tags with these characters."
    )]
    public const FORBIDDEN_CHARACTERS = "post_tags_forbidden_characters";
}
