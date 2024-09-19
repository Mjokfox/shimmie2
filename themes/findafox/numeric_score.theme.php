<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class CustomNumericScoreTheme extends NumericScoreTheme
{
    public function get_voter(Image $image): void
    {
        global $user, $page, $database;
        $user_id = $user->id;
        $i_image_id = $image->id;
        $i_score = (int)$image['numeric_score'];
        $i_vote = $this->get_my_vote($user_id,$i_image_id);
        $color = $i_score > 0 ? "lime" : ($i_score < 0 ? "red" : "gray");
        $html = "

			<div class='numeric-score' style='display:flex; flex-direction:row; align-items:center'>
                <div>"
                    .make_form(make_link("numeric_score_vote"))."
                    <input type='hidden' name='image_id' value='$i_image_id'>
                    <input type='hidden' name='vote' value='1'>
                    <button type='submit' title='upvote' ".($i_vote == 1 ? "style='color:lime;'" : "").">⬆</button>
                    </form>
                </div>

                <div title='current score' style='color:$color'><b>$i_score</b></div>

                <div>"
                    .make_form(make_link("numeric_score_vote"))."
                    <input type='hidden' name='image_id' value='$i_image_id'>
                    <input type='hidden' name='vote' value='-1'>
                    <button type='submit' title='downvote' ".($i_vote == -1 ? "style='color:red;'" : "").">⬇</button>
                    </form>
                </div>

                <div>"
                    .make_form(make_link("numeric_score_vote"))."
                    <input type='hidden' name='image_id' value='$i_image_id'>
                    <input type='hidden' name='vote' value='0'>
                    <button type='submit' title='remove vote' ".($i_vote == 0 ? "style='color:#009BE9;'" : "").">⬌</button>
                    </form>
                </div>
		";
        if (Extension::is_enabled(FavoritesInfo::KEY)){
            $is_favorited = $database->get_one(
                "SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = :user_id AND image_id = :image_id",
                ["user_id" => $user_id, "image_id" => $i_image_id]
            ) > 0;

            if ($is_favorited) {
                $url = "favourite/remove/{$i_image_id}";
                $value = "Un-Favorite";
            } else {
                $url ="favourite/add/{$i_image_id}";
                $value = "Favorite";
            }
            $html .= "<div>"
                    .make_form(make_link($url))."
                    <input type='submit' value='$value' title='$value' >
                    </form>
                </div>";
        }
        if ($user->can(Permissions::EDIT_OTHER_VOTE)) {
            $html .= 
            "<div id='votes-content'>
                <a
                    href='".make_link("numeric_score_votes/$i_image_id")."'
                    onclick='$(\"#votes-content\").load(\"".make_link("numeric_score_votes/$i_image_id")."\"); return false;'
                >See All Votes</a>
            </div>
            ";
            // <div style='padding-left:50vh;'>"
            //     .make_form(make_link("numeric_score/remove_votes_on"))."
            //     <input type='hidden' name='image_id' value='$i_image_id'>
            //     <input type='submit' value='Remove All Votes'>
            //     </form>
            // </div>
			// ";
        }
        $html .= "</div>";
        $page->add_block(new Block("", rawhtml($html), "main", 10,"Post_Scoremain"));
    }

    private function get_my_vote($user_id,$image_id) : int
    {
        global $database;
        return $database->get_one("SELECT score 
            FROM numeric_score_votes 
            WHERE user_id = :user_id 
            AND image_id = :image_id;",["user_id" => $user_id,"image_id"=>$image_id]) | 0;
    }
}
