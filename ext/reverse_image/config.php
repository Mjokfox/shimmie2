<?php

declare(strict_types=1);

namespace Shimmie2;

class ReverseImageConfig extends ConfigGroup
{
    public const KEY = "reverse_image";
    public const VERSION = "ext_reverse_image_version";

    #[ConfigMeta("Maximum reverse image search results: ", ConfigType::INT, default: 10)]
    public const CONF_MAX_LIMIT = "reverse_image_results_limit";

    #[ConfigMeta("Default reverse image search results: ", ConfigType::INT, default: 10)]
    public const CONF_DEFAULT_AMOUNT = "reverse_image_results_default";

    #[ConfigMeta("The similarity in % when its a duplicate: ", ConfigType::INT, default: 3)]
    public const SIMILARITY_DUPLICATE = "reverse_image_similarity_duplicate";

    #[ConfigMeta("Python engine url: ", ConfigType::STRING, default: "127.0.0.1:10017")]
    public const CONF_URL = "reverse_image_results_url";

    #[ConfigMeta("Enable descriptive text search: ", ConfigType::BOOL, default: true)]
    public const SEARCH_ENABLE = "reverse_image_search_enable";
}

class ReverseImageUserConfig extends UserConfigGroup
{
    public const KEY = "reverse_image";

    #[ConfigMeta("Enable automatic predicting: ", ConfigType::BOOL, default: true)]
    public const USER_ENABLE_AUTO = "reverse_image_automatic";

    #[ConfigMeta("Enable automatic tagging: ", ConfigType::BOOL, default: false)]
    public const USER_ENABLE_AUTO_SELECT = "reverse_image_automatic_select";

    #[ConfigMeta("The minimum percentage prediction to tag: ", ConfigType::INT, default: 50)]
    public const USER_TAG_THRESHOLD = "reverse_image_tag_threshold";

    #[ConfigMeta("Enable descriptive text search: ", ConfigType::BOOL, default: true)]
    public const USER_SEARCH_ENABLE = "reverse_image_user_search_enable";
}
