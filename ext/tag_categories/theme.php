<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON, DIV, H2, INPUT, P, SPAN, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

class TagCategoriesTheme extends Themelet
{
    public function show_tag_categories(): void
    {
        $html = emptyHTML();

        foreach (TagCategories::get_all_categories() as $row) {
            $name = $row['name'];
            $color = $row['color'];
            $priority = $row['priority'];

            $tags = Ctx::$database->get_col(
                "SELECT tags.tag
                FROM image_tag_categories_tags tct 
                JOIN tags ON tct.tag_id = tags.id
                WHERE tct.category_id = :category_id
                ORDER BY tct.id;",
                ["category_id" => $row['id']]
            );
            $tags = implode(" \n", $tags);

            $html->appendChild(DIV(
                ["class" => "tagcategoryblock tc-viewing"],
                SHM_SIMPLE_FORM(
                    make_link("tags/categories"),
                    INPUT(["type" => "hidden", "name" => "id", "value" => $row['id']]),
                    TABLE(
                        TR(
                            TD("Name"),
                            TD(
                                SPAN(["class" => "tc-view"], $name),
                                INPUT(["type" => "text", "name" => "name", "class" => "tc-edit", "value" => $name])
                            )
                        ),
                        TR(
                            TD("Color"),
                            TD(
                                SPAN(["class" => "tc-view"], $color),
                                DIV(["class" => "tc_colorswatch tc-view", "style" => "background-color:$color"]),
                                INPUT(["type" => "color", "name" => "color", "class" => "tc-edit", "value" => $color])
                            )
                        ),
                        TR(
                            TD("Priority"),
                            TD(
                                SPAN(["class" => "tc-view"], $priority),
                                INPUT(["type" => "number", "name" => "priority", "class" => "tc-edit", "value" => $priority]),
                            ),
                        ),
                        TR(
                            TD("tags"),
                            TD(
                                TEXTAREA(
                                    ["type" => "text", "name" => "tags", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5.5", "cols" => "15"],
                                    $tags
                                ),
                            ),
                        ),
                    ),
                    BUTTON([
                        "class" => "tc-view",
                        "type" => "button",
                        "data-tc-mode" => "tc-editing"
                    ], "Edit"),
                    BUTTON([
                        "class" => "tc-edit",
                        "type" => "submit",
                        "name" => "action",
                        "value" => "edit"
                    ], "Submit"),
                    BUTTON([
                        "class" => "tc-delete",
                        "type" => "submit",
                        "name" => "action",
                        "value" => "delete",
                        "data-shm-confirm" => "Are you sure you want to delete $name?"
                    ], "Delete"),
                )
            ));
        }

        // new
        $name = 'Example';
        $tag_color = '#EE5542';

        $html->appendChild(DIV(
            ["class" => "tagcategoryblock"],
            SHM_SIMPLE_FORM(
                make_link("tags/categories"),
                TABLE(
                    TR(
                        TD("Name"),
                        TD(
                            INPUT(["type" => "text", "name" => "name", "value" => $name])
                        )
                    ),
                    TR(
                        TD("Color"),
                        TD(
                            INPUT(["type" => "color", "name" => "color", "value" => $tag_color])
                        )
                    ),
                    TR(
                        TD("Priority"),
                        TD(
                            INPUT(["type" => "number", "name" => "priority", "value" => "0"]),
                        ),
                    ),
                    TR(
                        TD("tags"),
                        TD(
                            TEXTAREA(["type" => "text", "name" => "tags", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5", "cols" => "15"]),
                        ),
                    ),
                ),
                BUTTON(["class" => "tc_submit", "type" => "submit", "name" => "action", "value" => "new"], "Submit")
            ),
        ));

        // add html to stuffs
        Ctx::$page->set_title("Tag Categories");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Editing", $html, "main", 10));
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
        $dict = [];
        $dict[] = Ctx::$database->get_all(
            'SELECT tags.tag, tags.count
            FROM tags
            ORDER BY tags.count ASC;'
        );
        $dict[] = Ctx::$database->get_all(
            'SELECT tags.tag, tags.count
            FROM tags, image_tag_categories_tags itct
            WHERE tags.id = itct.tag_id
            ORDER BY tags.count ASC;'
        );
        $dict[] = Ctx::$database->get_all(
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
        Ctx::$page->set_title("Tag Categories counts");
        Ctx::$page->set_heading("Tag Categories counts");

        Ctx::$page->add_block(new Block("Tag Categories counts", DIV(["style" => "display:flex;justify-content:space-evenly;"], $html), "main", 10));
        $this->display_navigation();
    }

    public function display_admin_form(): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("admin/count_categories_tags"),
            SHM_SUBMIT('Display tag count'),
        );
        Ctx::$page->add_block(new Block("Tag categories", $html));
    }
}
