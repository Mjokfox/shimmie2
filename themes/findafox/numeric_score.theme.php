<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\DIV;
use function MicroHTML\INPUT;
use function MicroHTML\emptyHTML;

class CustomNumericScoreTheme extends NumericScoreTheme
{
    public function get_voter(Image $image): void
    {
        global $user, $page, $database;

        $vote_form = function (int $image_id, int $vote, string $text, ?string $style): HTMLElement {
            return SHM_SIMPLE_FORM(
                make_link("numeric_score/vote"),
                INPUT(['type' => 'hidden', 'name' => 'image_id', 'value' => $image_id]),
                INPUT(['type' => 'hidden', 'name' => 'vote', 'value' => $vote]),
                SHM_SUBMIT($text, ["style" => $style])
            );
        };
        $voters = null;
        if ($user->can(NumericScorePermission::EDIT_OTHER_VOTE)) {
            $voters = emptyHTML(
                BR(),
                DIV(
                    ["id" => "votes-content"],
                    A(
                        [
                            "href" => make_link("numeric_score/votes/$image->id"),
                            "onclick" => '$("#votes-content").load("'.make_link("numeric_score/votes/$image->id").'"); return false;',
                        ],
                        "See All Votes"
                    )
                ),
            );
        }

        $i_score = $image['numeric_score'];
        $i_vote = $this->get_my_vote($user->id, $image->id);
        $color = $i_score > 0 ? "lime" : ($i_score < 0 ? "red" : "gray");

        $fav = null;
        if (FavoritesInfo::is_enabled()) {
            $is_favorited = $database->get_one(
                "SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
                ["user_id" => $user->id, "image_id" => $image->id]
            ) > 0;

            if ($is_favorited) {
                $url = "favourite/remove/{$image->id}";
                $text = "♥";
            } else {
                $url = "favourite/add/{$image->id}";
                $text = "♡";
            }
            $fav = DIV(
                SHM_SIMPLE_FORM(
                    make_link($url),
                    SHM_SUBMIT($text, ["class" => "fav"])
                )
            );
        }
        $html = DIV(
            ["class" => "numeric-score", "style" => "display:flex; flex-direction:row; align-items:center"],
            DIV($vote_form($image->id, 1, "⬆", $i_vote == 1 ? "color:lime;" : null)),
            DIV(["title" => "Current score", "style" => "color:$color"], B($i_score)),
            DIV($vote_form($image->id, -1, "⬇", $i_vote == -1 ? "color:red;" : null)),
            DIV($vote_form($image->id, 0, "⬌", $i_vote == 0 ? "color:#009BE9;" : null)),
            $fav,
            $voters,
        );

        $page->add_block(new Block("", $html, "main", 10, "Post_Scoremain"));
    }

    private function get_my_vote(int $user_id, int $image_id): int
    {
        global $database;
        return $database->get_one("SELECT score 
            FROM numeric_score_votes 
            WHERE user_id = :user_id 
            AND image_id = :image_id;", ["user_id" => $user_id,"image_id" => $image_id]) | 0;
    }
}
