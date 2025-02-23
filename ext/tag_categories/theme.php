<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\DIV;

class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<array{category: string, upper_group: string, lower_group: string, color: string, upload_page_type: ?int, upload_page_priority: ?int}> $tc_dict
     */
    public function show_tag_categories(Page $page, array $tc_dict): void
    {
        global $database;

        $tc_block_index = 0;
        $html = '<div>';

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
            $type_map = ["hidden", "half width", "full width", "single column", "single row"];
            $html .= '
            <div class="tagcategoryblock tagcategorycategories">
            '.make_form(make_link("tags/categories")).'
                <table>
                <tr>
                    <td>Category</td>
                    <td>
                        <span>'.$tag_category.'</span>
                        <!--<input type="text" name="tc_category" style="display:none" value="'.$tag_category.'">-->
                        <input type="hidden" name="tc_category" value="'.$tag_category.'">
                    </td>
                </tr>
                <tr>
                    <td>List Group</td>
                    <td>
                        <span>'.$upper_group.'</span>
                        <input type="text" name="tc_up_group" style="display:none" value="'.$upper_group.'">
                    </td>
                </tr>
                <tr>
                    <td>Upload &ndash; Group</td>
                    <td>
                        <span>'.$lower_group.'</span>
                        <input type="text" name="tc_lo_group" style="display:none" value="'.$lower_group.'">
                    </td>
                </tr>
                <tr>
                    <td>Color</td>
                    <td>
                        <span>'.$tag_color.'</span><div class="tc_colorswatch" style="background-color:'.$tag_color.'"></div>
                        <input type="color" name="tc_color" style="display:none" value="'.$tag_color.'">
                    </td>
                </tr>
                <tr>
                    <td>Upload page</td>
                    <td>
                        <span>'.$type_map[$upload_page_type].'</span>
                        <select name="tc_up_type" style="display:none;">
                            <option value="0" '. ($upload_page_type == "0" ? "selected" : "") .'>hidden</option>
                            <option value="1" '. ($upload_page_type == "1" ? "selected" : "") .'>half width</option>
                            <option value="2" '. ($upload_page_type == "2" ? "selected" : "") .'>full width</option>
                            <option value="3" '. ($upload_page_type == "3" ? "selected" : "") .'>single column</option>
                            <option value="4" '. ($upload_page_type == "4" ? "selected" : "") .'>single row</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Priority</td>
                    <td>
                        <span>'.$upload_page_priority.'</span>
                        <input type="number" name="tc_up_prio" style="display:none" value="'.$upload_page_priority.'">
                    </td>
                </tr>
                <tr>
                    <td>tags</td>
                    <td>
                        <textarea type="text" name="tc_tag_list" class="autocomplete_tags" placeholder="tagme" rows="5.5" cols="15" readonly >'.$tags.'</textarea>
                    </td>
                    </tr>
                </table>
                <button class="tc_edit" type="button" onclick="$(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') tr + tr td span\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') td input\').show(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') td select\').show(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_edit\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_colorswatch\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_submit\').show(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.' ) textarea\').prop(\'readonly\', false);
                ">Edit</button>
                <button class="tc_submit" type="submit" style="display:none;" name="tc_status" value="edit">Submit</button>
                <button class="tc_submit" type="button" style="display:none;" onclick="$(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_delete\').show(); $(this).hide();">Delete</button>
                <button class="tc_delete" type="submit" style="display:none;" name="tc_status" value="delete">Really, really delete</button>
            </form>
            </div>
            ';
        }

        // new
        $tag_category = 'example';
        $upper_group = 'Example';
        $lower_group = 'Examples';
        $tag_color = '#EE5542';
        $html .= '
        <div class="tagcategoryblock">
        '.make_form(make_link("tags/categories")).'
            <table>
            <tr>
                <td>Category</td>
                <td>
                    <input type="text" name="tc_category" value="'.$tag_category.'">
                </td>
            </tr>
            <tr>
                <td>List &ndash; Group</td>
                <td>
                    <input type="text" name="tc_up_group" value="'.$upper_group.'">
                </td>
            </tr>
            <tr>
                <td>Upload &ndash; Group</td>
                <td>
                    <input type="text" name="tc_lo_group" value="'.$lower_group.'">
                </td>
            </tr>
            <tr>
                <td>Color</td>
                <td>
                    <input type="color" name="tc_color" value="'.$tag_color.'">
                </td>
            </tr>
            <tr>
                <td>Upload page</td>
                <td>
                    <select name="tc_up_type">
                        <option value="0">hidden</option>
                        <option value="1">half width</option>
                        <option value="2">full width</option>
                        <option value="3">single column</option>
                        <option value="4">single row</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Priority</td>
                <td>
                    <input type="number" name="tc_up_prio" value="0">
                </td>
            </tr>
            <tr>
                <td>tags</td>
                <td>
                    <textarea type="text" name="tc_tag_list" class="autocomplete_tags" placeholder="tagme" rows="5" cols="15"  ></textarea>
                </td>
            </tr>
            </table>
            <button class="tc_submit" type="submit" name="tc_status" value="new">Submit</button>
        </form>
        </div>
        </div>
        ';

        // add html to stuffs
        $page->set_title("Tag Categories");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Editing", rawHTML($html), "main", 10));
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts containing a certain number of tags with the specified tag category.</p>
        <div class="command_example">
        <code>persontags=1</code>
        <p>Returns posts with exactly 1 tag with the tag category "person".</p>
        </div>
        <div class="command_example">
        <code>cattags>0</code>
        <p>Returns posts with 1 or more tags with the tag category "cat". </p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <p>Category name is not case sensitive, category must exist for search to work.</p>
        ';
    }

    public function show_count_tag_categories(Page $page): void
    {
        global $database;
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
            $temphtml = "<div><h2>$label</h2><table class='table-odd noborders'><tr><th>tag</th><th>count</th><tr>";
            foreach ($dic as $row) {
                $temphtml .= "<tr><td>".$row["tag"]."</td><td>".$row["count"]."</td></tr>";
            }
            $temphtml .= "</table></div>";
            $html->appendChild(rawHTML($temphtml));
        }
        $page->set_title("Tag Categories counts");
        $page->set_heading("Tag Categories counts");

        $page->add_block(new Block("Tag Categories counts", DIV(["style" => "display:flex;justify-content:space-evenly;"], $html), "main", 10));
        $page->add_block(new NavBlock());
    }

    public function display_admin_form(): void
    {
        global $page;
        $html = (string)SHM_SIMPLE_FORM(
            "admin/count_categories_tags",
            SHM_SUBMIT('Display tag count'),
        );
        $page->add_block(new Block("Tag categories count", rawHTML($html)));
    }
}
