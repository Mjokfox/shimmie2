<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, BUTTON, DIV, FORM, INPUT, OPTION, P, SELECT, SMALL, TABLE, TD, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

/**
 * @phpstan-type similarArr array{image_id1:int,image_id2:int,ahash_distance:?int,dhash_distance:?int,phash_distance:?int,blockhash_distance:?int,least_distance:?int}
 */
class DuplicateDetectorTheme extends Themelet
{
    private const array hash_distance_types = [
        "least_distance" => "Smallest distance",
        "ahash_distance" => "Ahash distance",
        "dhash_distance" => "Dhash distance",
        "phash_distance" => "Phash distance",
        "blockhash_distance" => "Blockhash distance",
    ];
    /**
     * @param similarArr[] $similar_images
     */
    public function display_finder_page(array $similar_images, int $current_page, int $total_pages, bool $showing_ignored, string $sort_order, QueryArray $get): void
    {
        $html = emptyHTML();
        if ($current_page === 0) {
            $html->appendChild(SHM_SIMPLE_FORM(
                make_link("duplicate_finder"),
                BUTTON(['type' => 'button', 'onclick' => 'if(window.confirm("Are you sure you want to refresh the similar image database?"))this.form.submit()'], 'Refresh database'),
                BR(),
                B('Note, this takes can take very long, depending on how many images you have, if it times out, it is recommended to do this through the cli, or raise your php maximum timeout'),
                BR(),
                B("Another note, this also resets all ignored cases")
            ));
        }
        if (\count($similar_images) > 0) {
            $known_distance_types = [];
            $first = $similar_images[array_key_first($similar_images)];
            foreach (self::hash_distance_types as $key => $value) {
                if (isset($first[$key])) {
                    $known_distance_types[$key] = $value;
                }
            }

            $dropdown = SELECT(["name" => "sort"]);
            $dropdown_types = array_merge($known_distance_types, [
                "post_id_asc" => "post id (ascending)",
                "post_id_desc" => "post id (descending)"
            ]);
            foreach ($dropdown_types as $key => $value) {
                $attrs = ["value" => $key];
                if ($key === $sort_order) {
                    $attrs["selected"] = true;
                }
                $dropdown->appendChild(OPTION($attrs, $value));
            }

            $html->appendChild(FORM(
                ["action" => make_link("duplicate_finder"),"method" => "GET"],
                INPUT(["type" => "hidden", "name" => "show_ignored", "value" => $showing_ignored ? "true" : "false"]),
                $dropdown,
                SHM_SUBMIT("sort", ["style" => "padding:0 .2em"])
            ));

            if ($showing_ignored) {
                $html->appendChild(A(["href" => make_link("duplicate_finder")], "Return"));
            } else {
                $html->appendChild(A(["href" => make_link("duplicate_finder", ["show_ignored" => "true"])], "Show ignored"));
            }
            $post_cache = [];
            $table = DIV(["class" => "duplicate-table"]);
            foreach ($similar_images as $row) {
                if (!\array_key_exists($row["image_id1"], $post_cache)) {
                    $post_cache[$row["image_id1"]] = Post::by_id($row["image_id1"]);
                }
                if (!\array_key_exists($row["image_id2"], $post_cache)) {
                    $post_cache[$row["image_id2"]] = Post::by_id($row["image_id2"]);
                }
                $post1 = $post_cache[$row["image_id1"]];
                $post2 = $post_cache[$row["image_id2"]];
                if (is_null($post1) || is_null($post2)) {
                    continue;
                }

                $info = TABLE();
                foreach ($known_distance_types as $key => $value) {
                    $info->appendChild(TR(TD("$value:"), TD($row[$key])));
                }
                $info->appendChild(TR(TD(SHM_SIMPLE_FORM(
                    make_link("duplicate_finder/ignore"),
                    INPUT(["type" => "hidden", "name" => "image_id1", "value" => $row["image_id1"]]),
                    INPUT(["type" => "hidden", "name" => "image_id2", "value" => $row["image_id2"]]),
                    INPUT(["type" => "hidden", "name" => "set_ignore", "value" => $showing_ignored ? "false" : "true"]),
                    SHM_SUBMIT($showing_ignored ? "Restore" : "Ignore", ["style" => "padding:0 .2em"])
                ))));
                $table->appendChild(DIV(
                    ["class" => "duplicate-row"],
                    $info,
                    DIV(
                        ["class" => "duplicate-row-entry"],
                        $this->build_thumb($post1),
                        $this->build_thumb($post2),
                        $this->build_stats($post1),
                        $this->build_stats($post2)
                    )
                ));
            }
            $html->appendChild($table);
        }
        Ctx::$page->add_block(new Block(null, $html));
        $this->display_paginator("duplicate_finder", $get, $current_page + 1, $total_pages);
        Ctx::$page->set_title("Duplicate finder");
    }

    protected function build_stats(Post $image): HTMLElement
    {
        $parts = [
            "ID: {$image->id}",
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
            $parts[] = emptyHTML("Source: ", A(["href" => $image->source], preg_replace("#^https?://([^/]+)/.*#i", "$1", $image->source)));
        }

        return DIV(joinHTML(BR(), $parts));
    }

    /**
     * Only allows 1 file to be uploaded - for replacing another image file.
     */
    public function display_replace_page(int $image_id): void
    {
        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $accept = $this->get_accept();

        $max_size = Ctx::$config->get(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        $image = Post::by_id_ex($image_id);
        $thumbnail = $this->build_thumb($image);

        $form = SHM_FORM(make_link("duplicate_replace/$image_id"), multipart: true);
        $form->appendChild(emptyHTML(
            TABLE(
                ["id" => "large_replace_form", "class" => "form upload-form"],
                TR(
                    TD("File"),
                    TD(INPUT(["name" => "data", "type" => "file", "accept" => $accept]))
                ),
                $tl_enabled ? TR(
                    TD("or URL"),
                    TD(INPUT(["name" => "url", "type" => "text", "value" => @$_GET['url']]))
                ) : null,
                TR(TD("Source"), TD(["colspan" => 3], INPUT(["name" => "source", "type" => "text"]))),
                TR(TD(["colspan" => 4], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Post"]))),
            )
        ));

        $html = emptyHTML(
            P(
                "Replacing Post ID $image_id",
                BR(),
                "Please note: You will have to refresh the post page, or empty your browser cache."
            ),
            $thumbnail,
            BR(),
            $form,
            $max_size > 0 ? SMALL("(Max file size is $max_kb)") : null,
        );

        Ctx::$page->set_title("Replace File");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Upload Replacement File", $html, "main", 20));
    }

    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
