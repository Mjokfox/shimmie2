<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, FORM, INPUT, LINK, META, SPAN, TABLE, TD, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class CustomViewPostTheme extends ViewPostTheme
{
    public function display_meta_headers(Image $image): void
    {
        $page = Ctx::$page;
        $h_metatags = str_replace(" ", ", ", $image->get_tag_list());
        $page->add_html_header(META(["name" => "keywords", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "og:title", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "og:type", "content" => "article"]));
        $page->add_html_header(META(["property" => "og:image", "content" => $image->get_image_link()->asAbsolute()]));
        $page->add_html_header(META(["property" => "og:url", "content" => make_link("post/view/{$image->id}")->asAbsolute()]));
        $page->add_html_header(META(["property" => "og:image:width", "content" => $image->width]));
        $page->add_html_header(META(["property" => "og:image:height", "content" => $image->height]));
        $page->add_html_header(META(["property" => "twitter:title", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "twitter:card", "content" => "summary_large_image"]));
        $page->add_html_header(META(["property" => "twitter:image:src", "content" => $image->get_image_link()->asAbsolute()]));
        $page->add_html_header(META(["name" => "robots", "content" => \array_key_exists('search', $_GET) ? "nofollow noindex" : 'nofollow']));
    }
    /**
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts, array $sidebar_parts): void
    {
        Ctx::$page->set_heading($image->get_tag_list());
        $nav = $this->build_navigation($image);
        Ctx::$page->add_block(new Block("Search with tags", $nav, "left", 0, "search-bar"));
        Ctx::$page->add_block(new Block("Search with tags", $nav, "main", 5, "mobile-search"));
        Ctx::$page->add_block(new Block("Information", $this->build_stats($image), "left", 15));
        Ctx::$page->add_block(new Block(null, $this->build_info($image, $editor_parts, $sidebar_parts), "main", 15));
        Ctx::$page->add_block(new Block(null, $this->build_pin($image), "main", 2, "post_controls"));
    }

    protected function build_stats(Image $image): HTMLElement
    {
        $owner = $image->get_owner()->name;
        $ip = Ctx::$user->can(IPBanPermission::VIEW_IP) ? " ({$image->owner_ip})" : null;

        $parts = [
            "ID: {$image->id}",
            emptyHTML("Uploader: ", A(["href" => make_link("user/$owner")], $owner), $ip),
            emptyHTML("Date: ", SHM_DATE($image->posted)),
            "Size: ".to_shorthand_int($image->filesize)." ({$image->width}x{$image->height})",
            "Type: {$image->get_mime()}",
        ];
        if ($image->video_codec !== null) {
            $parts[] = "Video Codec: {$image->video_codec->name}";
        }
        if ($image->length !== null) {
            $parts[] = "Length: " . format_milliseconds($image->length);
        }
        if ($image->source !== null) {
            $parts[] = emptyHTML("Source: ", A(["href" => $image->source], "link"));
        }
        if (RatingsInfo::is_enabled()) {
            $rating = $image['rating'] ?? "?";
            $h_rating = Ratings::rating_to_human($rating);
            $parts[] = emptyHTML("Rating: ", A(["href" => search_link(["rating=$rating"])], $h_rating));
        }

        return joinHTML(BR(), $parts);
    }

    protected function build_navigation(Image $image): HTMLElement
    {
        $action = search_link();
        return FORM(
            [
                "action" => $action,
                "method" => "GET",
                "class" => "search-bar"
            ],
            INPUT(["type" => "hidden", "name" => "q", "value" => $action->getPath()]),
            INPUT(["type" => "hidden", "name" => "auth_token", "value" => Ctx::$user->get_auth_token()]),
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

    protected function build_info(Image $image, array $editor_parts, array $sidebar_parts = []): HTMLElement
    {
        if (count($editor_parts) === 0) {
            return emptyHTML($image->is_locked() ? "[Post Locked]" : "");
        }

        if (
            (!$image->is_locked() || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) &&
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG)
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
        $query = $this->get_query();
        Ctx::$page->add_html_header(LINK(["class" => "nextlink", "rel" => "next", "href" => make_link("post/next/{$image->id}", $query)]));
        Ctx::$page->add_html_header(LINK(["class" => "prevlink", "rel" => "previous", "href" => make_link("post/prev/{$image->id}", $query)]));
        return DIV(
            ["class" => "post-controls"],
            A(["href" => make_link("post/prev/{$image->id}", $query), "class" => "prevlink"], "<< Next"),
            SPAN(
                ["class" => "post-controls-center"],
                A(["href" => make_link("post/list/", $query), "id" => "searchlink"], "Search" . (isset($_GET['search']) ? ": ".$_GET['search'] : ""))
            ),
            A(["href" => make_link("post/next/{$image->id}", $query), "class" => "nextlink"], "Prev >>"),
        );
    }
}
