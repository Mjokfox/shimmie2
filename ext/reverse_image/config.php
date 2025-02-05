<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ReverseImageConfig
{
    public const VERSION = "ext_reverse_image_version";
    public const CONF_MAX_LIMIT = "reverse_image_results_limit";
    public const CONF_DEFAULT_AMOUNT = "reverse_image_results_default";
    public const SIMILARITY_DUPLICATE = "reverse_image_similarity_duplicate";
    public const CONF_URL = "reverse_image_results_url";
    public const USER_ENABLE_AUTO = "reverse_image_automatic";
    public const USER_ENABLE_AUTO_SELECT = "reverse_image_automatic_select";
    public const USER_TAG_THRESHOLD = "reverse_image_tag_threshold";
    public const SEARCH_ENABLE = "reverse_image_search_enable";
    public const USER_SEARCH_ENABLE = "reverse_image_user_search_enable";
}
