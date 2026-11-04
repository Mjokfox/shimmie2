<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostOverlayConfig extends ConfigGroup
{
    public const KEY = "post_overlay";

    #[ConfigMeta(
        "Disabled overlay info",
        ConfigType::ARRAY,
        options: "Shimmie2\PostOverlayConfig::get_config_options",
        help: "Select '[none]' to enable all."
    )]
    public const GLOBAL_DISABLED = "post_overlay_default_disabled";

    /**
     * @return array<string, string>
     */
    public static function get_config_options(): array
    {
        $enabled = array_keys(array_filter([
            "post_votes" => NumericScoreInfo::is_enabled(),
            "favorites" => FavoritesInfo::is_enabled(),
            "comments" => CommentListInfo::is_enabled(),
            "ratings" => RatingsInfo::is_enabled(),
        ]));
        $options = array_combine($enabled, $enabled);

        $null_option = PostOverlay::NULL_OPTION;
        $options[$null_option] = $null_option;

        return $options;
    }
}

final class PostOverlayUserConfig extends UserConfigGroup
{
    public const KEY = "post_overlay";

    #[ConfigMeta(
        "Disabled overlay info",
        ConfigType::ARRAY,
        options: "Shimmie2\PostOverlayConfig::get_config_options",
        help: "Select '[none]' to enable all."
    )]
    public const USER_DISABLED = "post_overlay_user_disabled";
}
