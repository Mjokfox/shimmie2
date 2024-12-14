<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, rawHTML, emptyHTML,DIV,H3, TABLE, COLGROUP, COL, THEAD, TH, TR, TD, SPAN};
use function MicroHTML\joinHTML;

class TagListTheme extends Themelet
{
    protected function get_tag_list_preamble(): HTMLElement
    {
        global $config;

        $tag_info_link_is_visible = !empty($config->get_string(TagListConfig::INFO_LINK));
        $tag_count_is_visible = $config->get_bool(TagListConfig::SHOW_NUMBERS);

        return TABLE(
            ["class" => "tag_list"],
            COLGROUP(
                ($tag_info_link_is_visible ? COL(["class" => "tag_info_link_column"]) : ''),
                COL(["class" => "tag_name_column"]),
                ($tag_count_is_visible ? COL(["class" => "tag_count_column"]) : '')
            ),
            THEAD(
                TR(
                    ($tag_info_link_is_visible ? TH(["class" => "tag_info_link_cell"]) : ''),
                    TH(["class" => "tag_name_cell"], "Tag"),
                    ($tag_count_is_visible ? TH(["class" => "tag_count_cell"], "#") : '')
                )
            ),
        );
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     * @param string[] $search
     */
    private function get_tag_list_html(array $tag_infos, string $sort, array $search = []): HTMLElement
    {
        if ($sort === TagListConfig::SORT_ALPHABETICAL) {
            usort($tag_infos, fn ($a, $b) => strcasecmp($a['tag'], $b['tag']));
        }

        $table = $this->get_tag_list_preamble();
        foreach ($tag_infos as $row) {
            $table->appendChild(self::build_tag_row($row, $search));
        }
        return $table;
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_split_related_block(Page $page, array $tag_infos): void
    {
        global $config;

        $search = in_array("search",$_GET) ? explode(" ",$_GET["search"]) : [""];

        if ($config->get_string(TagListConfig::RELATED_SORT) == TagListConfig::SORT_ALPHABETICAL) {
            usort($tag_infos, fn ($a, $b) => strcasecmp($a['tag'], $b['tag']));
        }

        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $tag_category_dict = TagCategories::getKeyedDict();
        } else {
            $tag_category_dict = [];
        }
        $tag_categories_html = [];
        $tag_categories_count = [];

        foreach ($tag_infos as $row) {
            $category = TagCategories::get_tag_category($row["tag"]);
            if (!isset($tag_categories_html[$category])) {
                $tag_categories_html[$category] = $this->get_tag_list_preamble();
            }
            $tag_categories_html[$category]->appendChild(self::build_tag_row($row,$search));

            if (!isset($tag_categories_count[$category])) {
                $tag_categories_count[$category] = 0;
            }
            $tag_categories_count[$category] += 1;
        }

        ksort($tag_categories_html);
        foreach (array_keys($tag_categories_html) as $category) {
            $tag_categories_html[$category] .= '</tbody></table>';
        }

        asort($tag_categories_html);
        if (isset($tag_categories_html[null])) {
            $main_html = $tag_categories_html[null];
        } else {
            $main_html = null;
        }
        unset($tag_categories_html[null]);
        $categories_display_names= [];
        foreach (array_keys($tag_categories_html) as $category) {
            if ($tag_categories_count[$category] < 2) {
                $categories_display_names[$tag_category_dict[$category]['display_singular']] = $tag_categories_html[$category];
            } else {
                $categories_display_names[$tag_category_dict[$category]['display_multiple']] = $tag_categories_html[$category];
            }
        }
        ksort($categories_display_names);
        if (array_key_exists("Meta", $categories_display_names)) {
            $metaLeftValue = $categories_display_names["Meta"];
            unset($categories_display_names["Meta"]);
            $categories_display_names["Meta"] = $metaLeftValue;
        }
        $tagshtml = emptyHTML();
        foreach (array_keys($categories_display_names) as $categories_display_name) {
            $tagshtml->appendChild(H3(html_escape($categories_display_name)));
            $tagshtml->appendChild(rawHTML($categories_display_names[$categories_display_name]));
        }


        if ($main_html !== null) {
            $tagshtml->appendChild(DIV(
                H3("Other"),
                DIV(["class" => "blockbody"],rawHTML($main_html)),
            ));
        }
        $page->add_block(new Block(null, $tagshtml, "left", 10,"Tagsleft"));
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_related_block(Page $page, array $tag_infos, string $block_name): void
    {
        global $config;

        $main_html = $this->get_tag_list_html(
            $tag_infos,
            $config->get_string(TagListConfig::RELATED_SORT)
        );

        $page->add_block(new Block($block_name, $main_html, "left", 10));
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_popular_block(Page $page, array $tag_infos): void
    {
        global $config;

        $main_html = emptyHTML(
            $this->get_tag_list_html(
                $tag_infos,
                $config->get_string(TagListConfig::POPULAR_SORT)
            ),
            rawHTML("&nbsp;"),
            BR(),
            A(["class" => "more", "href" => make_link("tags")], "Full List")
        );
        $page->add_block(new Block("Popular Tags", $main_html, "left", 60));
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     * @param string[] $search
     */
    public function display_refine_block(Page $page, array $tag_infos, array $search): void
    {
        global $config;
        $main_html = emptyHTML(
            $this->get_tag_list_html(
                $tag_infos,
                $config->get_string(TagListConfig::POPULAR_SORT),
                $search
            ),
            rawHTML("&nbsp;"),
            BR(),
            A(["class" => "more", "href" => make_link("tags")], "Full List")
        );

        $page->add_block(new Block("Refine Search", $main_html, "left", 60));
    }

    /**
     * @param array{tag: string, count: int} $row
     * @param string[] $search
     */
    protected function build_tag_row(array $row, array $search = []): HTMLElement
    {
        global $config;

        $tag = $row['tag'];
        $count = $row['count'];
        $props = [];
        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $category = TagCategories::get_tag_category($tag);
            if (!is_null($category)) {
                $tag_category_dict = TagCategories::getKeyedDict();
                $props["style"] = "color:".$tag_category_dict[$category]['color'].";";
            }
        }
        $tr = TR($props);

        $info_link_template = $config->get_string(TagListConfig::INFO_LINK);
        if (!empty($info_link_template)) {
            $tr->appendChild(TD(
                ["class" => "tag_info_link_cell"],
                A(
                    [
                        "class" => "tag_info_link",
                        "href" => str_replace('$tag', url_escape($tag), $info_link_template),
                    ],
                    "?"
                )
            ));
        }

        $tr->appendChild(TD(
            ["class" => "tag_name_cell"],
            emptyHTML(
                $search ? $this->ars($search, $tag) : emptyHTML(), " ",
                $this->build_tag($tag, show_underscores: false, show_category: false),
            )
        ));

        if ($config->get_bool(TagListConfig::SHOW_NUMBERS)) {
            $tr->appendChild(TD(
                ["class" => "tag_count_cell"],
                SPAN(["class" => "tag_count"], $count)
            ));
        }

        return $tr;
    }

    /**
     * @param string[] $tags
     */
    protected function ars(array $tags, string $tag): HTMLElement
    {
        // FIXME: a better fix would be to make sure the inputs are correct
        $tag = strtolower($tag);
        $tags = array_map("strtolower", $tags);
        return SPAN(
            ["class" => "ars"],
            joinHTML(
                " ",
                [
                    $this->get_add_link($tags, $tag),
                    $this->get_remove_link($tags, $tag),
                    $this->get_subtract_link($tags, $tag),
                ]
            ),
        );
    }

    /**
     * @param string[] $search
     */
    protected function get_remove_link(array $search, string $tag): ?HTMLElement
    {
        if (in_array($tag, $search) || in_array("-$tag", $search)) {
            $new_search = array_diff($search, [$tag, "-$tag"]);
            return A(["href" => search_link($new_search), "title" => "Remove", "rel" => "nofollow",], "x");
        }
        return null;
    }

    /**
     * @param string[] $search
     */
    protected function get_add_link(array $search, string $tag): ?HTMLElement
    {
        if (!in_array($tag, $search)) {
            $new_search = array_merge(array_diff($search, ["-$tag"]), [$tag]);
            return A(["href" => search_link($new_search), "title" => "Add", "rel" => "nofollow"], "+");
        }
        return null;
    }

    /**
     * @param string[] $search
     */
    protected function get_subtract_link(array $search, string $tag): ?HTMLElement
    {
        if (!in_array("-$tag", $search)) {
            $search = array_merge(array_diff($search, [$tag]), ["-$tag"]);
            return A(["href" => search_link($search), "title" => "Subtract", "rel" => "nofollow"], "–");
        }
        return null;
    }
}
