<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON, DIV, H2, INPUT, OPTION, P, SELECT, SPAN, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<array{category: string, upper_group: string, lower_group: string, color: string, upload_page_type: ?int, upload_page_priority: ?int}> $tc_dict
     */
    public function show_tag_categories(array $tc_dict): void
    {
        global $database;
        $tc_block_index = 0;
        $html = [];

        foreach ($tc_dict as $row) {
            $tc_block_index += 1;
            $tag_category = $row['category'];
            $query = "
            SELECT tags.tag
            FROM image_tag_categories_tags tct 
            JOIN tags ON tct.tag_id = tags.id
            WHERE tct.category_id = (
                SELECT id FROM image_tag_categories WHERE category = :category_name
            )
            ORDER BY tct.id;";
            $args = ["category_name" => $tag_category];
            $tags = $database->get_col($query, $args);
            $tags = implode(' ', $tags);
            $tags = str_replace(' ', " \n", $tags);

            $upper_group = $row['upper_group'];
            $lower_group = $row['lower_group'];
            $tag_color = $row['color'];
            $upload_page_type = $row['upload_page_type'] ?? 0;
            $upload_page_priority = $row['upload_page_priority'] ?? 0;
            $type_map = ["hidden", "half width", "full width", "single column", "dropdown", "single row", "single row (half width)", "info line"];
            $html[] = DIV(
                ["class" => "tagcategoryblock tagcategorycategories"],
                SHM_SIMPLE_FORM(
                    make_link("tags/categories"),
                    TABLE(
                        TR(
                            TD("Category"),
                            TD(
                                SPAN($tag_category),
                                INPUT(["type" => "hidden", "name" => "tc_category", "value" => $tag_category])
                            )
                        ),
                        TR(
                            TD("List - Group"),
                            TD(
                                SPAN($upper_group),
                                INPUT(["type" => "text", "name" => "tc_up_group", "style" => "display:none", "value" => $upper_group])
                            )
                        ),
                        TR(
                            TD("Upload - Group"),
                            TD(
                                SPAN($lower_group),
                                INPUT(["type" => "text", "name" => "tc_lo_group", "style" => "display:none", "value" => $lower_group])
                            )
                        ),
                        TR(
                            TD("Color"),
                            TD(
                                SPAN($tag_color),
                                DIV(["class" => "tc_colorswatch", "style" => "background-color:$tag_color"]),
                                INPUT(["type" => "color", "name" => "tc_color", "style" => "display:none", "value" => $tag_color])
                            )
                        ),
                        TR(
                            TD("Upload page"),
                            TD(
                                SPAN($type_map[$upload_page_type]),
                                SELECT(
                                    ["name" => "tc_up_type", "style" => "display:none;"],
                                    OPTION(["value" => "0", ($upload_page_type === 0 ? "selected" : "") => true], "hidden"),
                                    OPTION(["value" => "1", ($upload_page_type === 1 ? "selected" : "") => true], "half width"),
                                    OPTION(["value" => "2", ($upload_page_type === 2 ? "selected" : "") => true], "full width"),
                                    OPTION(["value" => "3", ($upload_page_type === 3 ? "selected" : "") => true], "single column"),
                                    OPTION(["value" => "4", ($upload_page_type === 4 ? "selected" : "") => true], "dropdown"),
                                    OPTION(["value" => "5", ($upload_page_type === 5 ? "selected" : "") => true], "single row"),
                                    OPTION(["value" => "6", ($upload_page_type === 6 ? "selected" : "") => true], "single row (half width)"),
                                    OPTION(["value" => "7", ($upload_page_type === 7 ? "selected" : "") => true], "info line"),
                                ),
                            ),
                        ),
                        TR(
                            TD("Priority"),
                            TD(
                                SPAN($upload_page_priority),
                                INPUT(["type" => "number", "name" => "tc_up_prio", "style" => "display:none;", "value" => $upload_page_priority]),
                            ),
                        ),
                        TR(
                            TD("tags"),
                            TD(
                                TEXTAREA(
                                    ["type" => "text", "name" => "tc_tag_list", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5.5", "cols" => "15", "readonly" => true],
                                    $tags
                                ),
                            ),
                        ),
                    ),
                    BUTTON([
                        "class" => "tc_edit",
                        "type" => "button",
                        "onclick" => "
                            $('.tagcategorycategories:nth-of-type($tc_block_index) tr + tr td span').hide();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) td input').show();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) td select').show();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) .tc_edit').hide();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) .tc_colorswatch').hide();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) .tc_submit').show();
                            $('.tagcategorycategories:nth-of-type($tc_block_index) textarea').prop('readonly', false);
                        "
                    ], "Edit"),
                    BUTTON([
                        "class" => "tc_submit",
                        "type" => "submit",
                        "style" => "display:none;",
                        "name" => "tc_status",
                        "value" => "edit"
                    ], "Submit"),
                    BUTTON([
                        "class" => "tc_submit",
                        "type" => "button",
                        "style" => "display:none;",
                        "onclick" => "$('.tagcategoryblock:nth-of-type($tc_block_index) .tc_delete').show(); $(this).hide();",
                    ], "Delete"),
                    BUTTON([
                        "class" => "tc_delete",
                        "type" => "submit",
                        "style" => "display:none;",
                        "name" => "tc_status",
                        "value" => "delete",
                    ], "Really, really delete"),
                )
            );
        }

        // new
        $tag_category = 'example';
        $upper_group = 'Example';
        $lower_group = 'Examples';
        $tag_color = '#EE5542';
        $html[] = DIV(
            ["class" => "tagcategoryblock"],
            SHM_SIMPLE_FORM(
                make_link("tags/categories"),
                TABLE(
                    TR(
                        TD("Category"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_category", "value" => $tag_category])
                        )
                    ),
                    TR(
                        TD("List - Group"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_up_group", "value" => $upper_group])
                        )
                    ),
                    TR(
                        TD("Upload - Group"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_lo_group", "value" => $lower_group])
                        )
                    ),
                    TR(
                        TD("Color"),
                        TD(
                            INPUT(["type" => "color", "name" => "tc_color", "value" => $tag_color])
                        )
                    ),
                    TR(
                        TD("Upload page"),
                        TD(
                            SELECT(
                                ["name" => "tc_up_type"],
                                OPTION(["value" => "0"], "hidden"),
                                OPTION(["value" => "1"], "half width"),
                                OPTION(["value" => "2"], "full width"),
                                OPTION(["value" => "3"], "single column"),
                                OPTION(["value" => "4"], "dropdown"),
                                OPTION(["value" => "5"], "single row"),
                                OPTION(["value" => "6"], "single row (half width)"),
                                OPTION(["value" => "7"], "info line"),
                            ),
                        ),
                    ),
                    TR(
                        TD("Priority"),
                        TD(
                            INPUT(["type" => "number", "name" => "tc_up_prio", "value" => "0"]),
                        ),
                    ),
                    TR(
                        TD("tags"),
                        TD(
                            TEXTAREA(["type" => "text", "name" => "tc_tag_list", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5", "cols" => "15"]),
                        ),
                    ),
                ),
                BUTTON(["class" => "tc_submit", "type" => "submit", "name" => "tc_status", "value" => "new"], "Submit")
            ),
        );

        // add html to stuffs
        Ctx::$page->set_title("Tag Categories");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Editing", joinHTML("\n", $html), "main", 10));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts containing a certain number of tags with the specified tag category."),
            SHM_COMMAND_EXAMPLE("person_tags=1", "Returns posts with exactly 1 tag with the tag category 'person'."),
            SHM_COMMAND_EXAMPLE("cat_tags>0", "Returns posts with 1 or more tags with the tag category 'cat'."),
            P("Can use <, <=, >, >=, or =."),
            P("Category name is not case sensitive, category must exist for search to work.")
        );
    }

    public function show_count_tag_categories(): void
    {
        global $page;
        global $database;
        $dict = [];
        $dict[] = $database->get_all(
            'SELECT tags.tag, tags.count
            FROM tags
            ORDER BY tags.count ASC;'
        );
        $dict[] = $database->get_all(
            'SELECT tags.tag, tags.count
            FROM tags, image_tag_categories_tags itct
            WHERE tags.id = itct.tag_id
            ORDER BY tags.count ASC;'
        );
        $dict[] = $database->get_all(
            'SELECT tags.tag, tags.count
            FROM tags
            WHERE tags.id NOT IN (SELECT itct.tag_id FROM image_tag_categories_tags itct)
            ORDER BY tags.count ASC;'
        );
        $labels = ["All tags", "In categories", "Outside categories"];
        $i = 0;
        $html = emptyHTML();
        foreach ($dict as $dic) {
            $label = $labels[$i++];
            $tbody = TBODY();
            foreach ($dic as $row) {
                $tbody->appendChild(TR(
                    TD($row["tag"]),
                    TD($row["count"])
                ));
            }
            $thtml = DIV(
                H2($label),
                TABLE(
                    ["class" => "table-odd noborders"],
                    THEAD(
                        TH("tag"),
                        TH("count"),
                    ),
                    $tbody
                )
            );
            $html->appendChild($thtml);
        }
        $page->set_title("Tag Categories counts");
        $page->set_heading("Tag Categories counts");

        $page->add_block(new Block("Tag Categories counts", DIV(["style" => "display:flex;justify-content:space-evenly;"], $html), "main", 10));
        $this->display_navigation();
    }

    public function display_admin_form(): void
    {
        global $page;
        $html = SHM_SIMPLE_FORM(
            make_link("admin/count_categories_tags"),
            SHM_SUBMIT('Display tag count'),
        );
        $page->add_block(new Block("Tag categories count", $html));
    }
}
