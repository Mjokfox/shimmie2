<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML, A, DIV, SPAN, LINK, rawHTML, TR, TD, INPUT, TABLE, FORM};

class CustomViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        global $page;
        $page->set_heading(html_escape($image->get_tag_list()));
        $nav = $this->build_navigation($image);
        $page->add_block(new Block("Search with tags", $nav, "left", 0, "search-bar"));
        $page->add_block(new Block("Search with tags", $nav, "main", 5, "mobile-search"));
        $page->add_block(new Block("Information", $this->build_information($image), "left", 15));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 15));
        $page->add_block(new Block(null, $this->build_pin($image), "main", 2, "post_controls"));
    }

    private function build_information(Image $image): HTMLElement
    {
        $h_owner = html_escape($image->get_owner()->name);
        $h_ownerlink = "<a href='".make_link("user/$h_owner")."'>$h_owner</a>";
        $h_ip = html_escape($image->owner_ip);
        $h_type = html_escape($image->get_mime()->base);
        $h_date = SHM_DATE($image->posted);
        $h_filesize = to_shorthand_int($image->filesize);

        global $user;
        if ($user->can(IPBanPermission::VIEW_IP)) {
            $h_ownerlink .= " ($h_ip)";
        }

        $html = "
		ID: {$image->id}
		<br>Uploader: $h_ownerlink
		<br>Date: $h_date
		<br>Size: $h_filesize ({$image->width}x{$image->height})
		<br>Type: $h_type
		";

        if ($image->length != null) {
            $h_length = format_milliseconds($image->length);
            $html .= "<br/>Length: $h_length";
        }


        if (!is_null($image->source)) {
            $source = $image->source;
            if (!str_contains($source, "://")) {
                $source = "https://" . $source;
            }
            $html .= "<br>Source: <a href='$source'>link</a>";
        }

        if (RatingsInfo::is_enabled()) {
            $rating = $image['rating'];
            if ($rating === null) {
                $rating = "?";
            }
            $h_rating = Ratings::rating_to_human($rating);
            $html .= "<br>Rating: <a href='".search_link(["rating=$rating"])."'>$h_rating</a>";
        }

        if (NumericScoreInfo::is_enabled()) {
            $h_score = (int)$image['numeric_score'];
            $score_color = $h_score > 0 ? "lime" : ($h_score < 0 ? "red" : "gray");
            $html .= "<br>Score: <span style='color:$score_color'>$h_score</span>";
        }

        return rawHTML($html);
    }

    protected function build_navigation(Image $image): HTMLElement
    {
        global $user;
        $action = search_link();
        return FORM(
            [
                "action" => $action,
                "method" => "GET",
                "class" => "search-bar"
            ],
            INPUT(["type" => "hidden", "name" => "q", "value" => $action->getPath()]),
            INPUT(["type" => "hidden", "name" => "auth_token", "value" => $user->get_auth_token()]),
            INPUT([
                "name" => 'search',
                "type" => 'text',
                "class" => 'autocomplete_tags',
                "placeholder" => 'tags',
                "value" => $_GET['search'] ?? ""
            ]),
            SHM_SUBMIT("Go!"),
        );
    }

    protected function build_info(Image $image, array $editor_parts): HTMLElement
    {
        global $config, $user;

        if (count($editor_parts) == 0) {
            return emptyHTML($image->is_locked() ? "[Post Locked]" : "");
        }

        if (
            (!$image->is_locked() || $user->can(PostLockPermission::EDIT_IMAGE_LOCK)) &&
            $user->can(PostTagsPermission::EDIT_IMAGE_TAG)
        ) {
            $editor_parts[] = TR(TD(
                ["colspan" => 4],
                INPUT(["class" => "view", "type" => "button", "value" => "Edit", "onclick" => "clearViewMode()"]),
                INPUT(["class" => "edit", "type" => "submit", "value" => "Set"])
            ));
        }

        return SHM_SIMPLE_FORM(
            make_link("post/set"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image->id]),
            TABLE(
                [
                    "class" => "image_info form",
                ],
                ...$editor_parts,
            ),
        );
    }

    protected function build_pin(Image $image): HTMLElement
    {
        global $page;
        $query = $this->get_query();
        $page->add_html_header(LINK(["id" => "nextlink", "rel" => "next", "href" => make_link("post/next/{$image->id}", $query)]));
        $page->add_html_header(LINK(["id" => "prevlink", "rel" => "previous", "href" => make_link("post/prev/{$image->id}", $query)]));
        return DIV(
            ["class" => "post-controls"],
            A(["href" => make_link("post/prev/{$image->id}", $query), "id" => "prevlink"], "<< Next"),
            SPAN(
                ["class" => "post-controls-center"],
                A(["href" => make_link("post/list/", $query), "id" => "searchlink"], "Search" . (isset($_GET['search']) ? ": ".$_GET['search'] : ""))
            ),
            A(["href" => make_link("post/next/{$image->id}", $query), "id" => "nextlink"], "Prev >>"),
        );
    }
}
