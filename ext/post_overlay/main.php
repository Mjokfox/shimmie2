<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\SCRIPT;

final class PostOverlay extends Extension
{
    public const KEY = "post_overlay";

    public const NULL_OPTION = "[none]";

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        if (empty($event->images)) {
            return;
        }
        $ids = array_map(fn (Post $i) => $i->id, $event->images);

        $min = min($ids);
        $max = max($ids);

        $data = [];
        if (!Ctx::$user->is_anonymous()) {
            $data["auth_token"] = Ctx::$user->get_auth_token();
        }

        $enabled = [];

        $disabled = array_merge(
            Ctx::$config->get(PostOverlayConfig::GLOBAL_DISABLED) ?? [],
            Ctx::$user->get_config()->get(PostOverlayUserConfig::USER_DISABLED) ?? []
        );

        if (!in_array("post_votes", $disabled) && NumericScoreInfo::is_enabled()) {
            $data["scores"] = Ctx::$database->get_pairs(
                "SELECT image_id, score FROM numeric_score_votes
            where user_id = :user_id
            AND image_id BETWEEN :min AND :max",
                ["user_id" => Ctx::$user->id, "min" => $min, "max" => $max]
            );
            $enabled[] = "post_votes";
        }

        if (!in_array("favorites", $disabled) && FavoritesInfo::is_enabled()) {
            $data["favorites"] = Ctx::$database->get_col(
                "SELECT image_id FROM user_favorites
            where user_id = :user_id
            AND image_id BETWEEN :min AND :max",
                ["user_id" => Ctx::$user->id, "min" => $min, "max" => $max]
            );
            $enabled[] = "favorites";
        }

        if (!in_array("comments", $disabled)) {
            $enabled[] = "comments";
        }

        if (!in_array("ratings", $disabled) && RatingsInfo::is_enabled()) {
            $enabled[] = "ratings";
        }

        Ctx::$page->add_html_header(SCRIPT(
            ["type" => "text/javascript"],
            \MicroHTML\rawHTML("window.post_overlay_data = ".\Safe\json_encode($data).";window.post_overlay_enabled = ".\Safe\json_encode($enabled).";")
        ));
    }
}
