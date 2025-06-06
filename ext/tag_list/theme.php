<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, COL, COLGROUP, SPAN, TABLE, TD, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\joinHTML;

class TagListTheme extends Themelet
{
    protected function get_tag_list_preamble(): HTMLElement
    {
        $tag_info_link_is_visible = !empty(Ctx::$config->get(TagListConfig::INFO_LINK));
        $tag_count_is_visible = Ctx::$config->get(TagListConfig::SHOW_NUMBERS);

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
     * @param array<array{tag: tag-string, count: int}> $tag_infos
     * @param search-term-array $search
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
     * @param array<array{tag: tag-string, count: int}> $tag_infos
     */
    public function display_split_related_block(array $tag_infos): void
    {
        if (Ctx::$config->get(TagListConfig::RELATED_SORT) === TagListConfig::SORT_ALPHABETICAL) {
            usort($tag_infos, fn ($a, $b) => strcasecmp($a['tag'], $b['tag']));
        }

        if (TagCategoriesInfo::is_enabled()) {
            $tag_category_dict = TagCategories::getKeyedDict();
        } else {
            $tag_category_dict = [];
        }
        $tag_categories_html = [];
        $tag_categories_count = [];

        foreach ($tag_infos as $row) {
            $tag = $row['tag'];
            $category = TagCategories::get_tag_category($tag);
            if (!isset($tag_categories_html[$category])) {
                $tag_categories_html[$category] = $this->get_tag_list_preamble();
            }
            $tag_categories_html[$category]->appendChild(self::build_tag_row($row));

            if (!isset($tag_categories_count[$category])) {
                $tag_categories_count[$category] = 0;
            }
            $tag_categories_count[$category] += 1;
        }

        ksort($tag_categories_html);
        foreach (array_keys($tag_categories_html) as $category) {
            if ($category === '') {
                $category_display_name = 'Tags';
                $prio = 10;
            } elseif ($tag_categories_count[$category] < 2) {
                $category_display_name = $tag_category_dict[$category]['display_singular'];
                $prio = 9;
            } else {
                $category_display_name = $tag_category_dict[$category]['display_multiple'];
                $prio = 9;
            }
            Ctx::$page->add_block(new Block($category_display_name, $tag_categories_html[$category], "left", $prio));
        }
    }

    /**
     * @param array<array{tag: tag-string, count: int}> $tag_infos
     */
    public function display_related_block(array $tag_infos, string $block_name): void
    {
        $main_html = $this->get_tag_list_html(
            $tag_infos,
            Ctx::$config->get(TagListConfig::RELATED_SORT)
        );

        Ctx::$page->add_block(new Block($block_name, $main_html, "left", 10));
    }

    /**
     * @param array<array{tag: tag-string, count: int}> $tag_infos
     */
    public function display_popular_block(array $tag_infos): void
    {
        $main_html = emptyHTML(
            $this->get_tag_list_html(
                $tag_infos,
                Ctx::$config->get(TagListConfig::POPULAR_SORT)
            ),
            " ",
            BR(),
            A(["class" => "more", "href" => make_link("tags")], "Full List")
        );
        Ctx::$page->add_block(new Block("Popular Tags", $main_html, "left", 60));
    }

    /**
     * @param array<array{tag: tag-string, count: int}> $tag_infos
     * @param search-term-array $search
     */
    public function display_refine_block(array $tag_infos, array $search): void
    {
        $main_html = emptyHTML(
            $this->get_tag_list_html(
                $tag_infos,
                Ctx::$config->get(TagListConfig::POPULAR_SORT),
                $search
            ),
            " ",
            BR(),
            A(["class" => "more", "href" => make_link("tags")], "Full List")
        );

        Ctx::$page->add_block(new Block("Refine Search", $main_html, "left", 60));
    }

    /**
     * @param array{tag: tag-string, count: int} $row
     * @param search-term-array $search
     */
    protected function build_tag_row(array $row, array $search = []): HTMLElement
    {
        $tag = $row['tag'];

        $tr = TR();

        $info_link_template = Ctx::$config->get(TagListConfig::INFO_LINK);
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
                $this->build_tag($tag, show_underscores: false, show_category: false),
                " ",
                $search ? $this->ars($search, $tag) : emptyHTML(),
            )
        ));

        if (Ctx::$config->get(TagListConfig::SHOW_NUMBERS)) {
            $tr->appendChild(TD(
                ["class" => "tag_count_cell"],
                SPAN(["class" => "tag_count"], $row['count'])
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
        $tags = array_map(strtolower(...), $tags);
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
            /** @var search-term-array $new_search */
            $new_search = array_diff($search, [$tag, "-$tag"]);
            return A(["href" => search_link($new_search), "title" => "Remove", "rel" => "nofollow",], "[x]");
        }
        return null;
    }

    /**
     * @param string[] $search
     */
    protected function get_add_link(array $search, string $tag): ?HTMLElement
    {
        if (!in_array($tag, $search)) {
            /** @var search-term-array $new_search */
            $new_search = array_merge(array_diff($search, ["-$tag"]), [$tag]);
            return A(["href" => search_link($new_search), "title" => "Add", "rel" => "nofollow"], "[+]");
        }
        return null;
    }

    /**
     * @param string[] $search
     */
    protected function get_subtract_link(array $search, string $tag): ?HTMLElement
    {
        if (!in_array("-$tag", $search)) {
            /** @var search-term-array $new_search */
            $new_search = array_merge(array_diff($search, [$tag]), ["-$tag"]);
            return A(["href" => search_link($new_search), "title" => "Subtract", "rel" => "nofollow"], "[-]");
        }
        return null;
    }
}
