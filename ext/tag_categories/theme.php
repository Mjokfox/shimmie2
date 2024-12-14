<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\DIV;

class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<array{category: string, display_singular: string, display_multiple: string, color: string}> $tc_dict
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
            FROM image_tag_categories_tags tc, tags
            WHERE tc.tag_id = tags.id
            AND tc.category_id = (
                SELECT id FROM image_tag_categories WHERE category = :category_name
            );";
            $args = ["category_name" => $tag_category];
            $tags = $database->get_col($query, $args);
            $tags = Tag::implode($tags);
            $tags = str_replace(' ', " \n", $tags);

            $tag_single_name = $row['display_singular'];
            $tag_multiple_name = $row['display_multiple'];
            $tag_color = $row['color'];
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
                    <td>Name &ndash; Single</td>
                    <td>
                        <span>'.$tag_single_name.'</span>
                        <input type="text" name="tc_display_singular" style="display:none" value="'.$tag_single_name.'">
                    </td>
                </tr>
                <tr>
                    <td>Name &ndash; Multiple</td>
                    <td>
                        <span>'.$tag_multiple_name.'</span>
                        <input type="text" name="tc_display_multiple" style="display:none" value="'.$tag_multiple_name.'">
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
                    <td>tags</td>
                    <td>
                        <textarea type="text" name="tc_tag_list" class="autocomplete_tags" placeholder="tagme" rows="5.5" cols="15" readonly >'.$tags.'</textarea>
                    </td>
                    </tr>
                </table>
                <button class="tc_edit" type="button" onclick="$(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') tr + tr td span\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') td input\').show(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_edit\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_colorswatch\').hide(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.') .tc_submit\').show(); $(\'.tagcategorycategories:nth-of-type('.$tc_block_index.' ) textarea\').prop(\'readonly\', false);
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
        $tag_single_name = 'Example';
        $tag_multiple_name = 'Examples';
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
                <td>Name &ndash; Single</td>
                <td>
                    <input type="text" name="tc_display_singular" value="'.$tag_single_name.'">
                </td>
            </tr>
            <tr>
                <td>Name &ndash; Multiple</td>
                <td>
                    <input type="text" name="tc_display_multiple" value="'.$tag_multiple_name.'">
                </td>
            </tr>
            <tr>
                <td>Color</td>
                <td>
                    <input type="color" name="tc_color" value="'.$tag_color.'">
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

        // category settings
        /*
        $html .= '
        <div style="text-align:left;position:relative;">
            <div style="height:5px; width:100%; background-color:white;"></div>
            <h3 style="margin-top:5px;">Settings (not functioning yet)<h3>
        </div>
        <div>';
        $tc_block_index = 0;
        $settings = $database->get_all('SELECT * FROM image_tag_categories_settings');

        foreach($settings as $row){
            $tc_block_index += 1;
            $query = "
            SELECT tags.tag
            FROM image_tag_categories_settings_tags tcs, tags
            WHERE tcs.tag_id = tags.id
            AND tcs.setting_id = :setting_id
            ;";
            $args = ["setting_id" => $row['id']];
            $tags = $database->get_col($query, $args);
            $tags = Tag::implode($tags);
            $tags = str_replace(' ', " \n", $tags);

            $tag_id = $row['tag_id'];
            $tag = $database->get_one("SELECT tags.tag FROM tags WHERE tags.id = :id",['id' => $tag_id]);
            $setting_type = $row['setting_type'];
            $setting_types = ['set hide', 'set show', 'make checkbox', 'make radio'];
            $html .= '
            <div class="tagcategoryblock tagcategorysettings">
            '.make_form(make_link("tags/categories")).'
                <table>
                <tr>
                    <td>Controlling tag</td>
                    <td>
                        <span>'.$tag.'</span>
                        <input type="text" name="tc_setting_tag" style="display:none" value="'.$tag.'">
                    </td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>
                        <span style="width:auto">'.$setting_types[$setting_type].'</span>
                        <select name="tc_setting_type" id="setting_type" style="display:none">
                            <option value="0" '.($setting_type == 0 ? 'selected' : '' ).'>set hide</option>
                            <option value="1" '.($setting_type == 1 ? 'selected' : '' ).'>set show</option>
                            <option value="2" '.($setting_type == 2 ? 'selected' : '' ).'>make checkbox</option>
                            <option value="3" '.($setting_type == 3 ? 'selected' : '' ).'>make radio</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>affected tags</td>
                    <td>
                        <textarea type="text" name="tc_setting_tag_list" class="autocomplete_tags" placeholder="tagme" rows="5" cols="15" readonly >'.$tags.'</textarea>
                    </td>
                </tr>
                </table>
                <button class="tc_edit" type="button" onclick="
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') tr td span\').hide();
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') td input\').show();
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') td select\').show();
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') .tc_edit\').hide();
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') .tc_submit\').show();
                $(\'.tagcategorysettings:nth-of-type('.$tc_block_index.' ) textarea\').prop(\'readonly\', false);
                ">Edit</button>
                <button class="tc_submit" type="submit" style="display:none;" name="tc_setting_status" value="edit">Submit</button>
                <button class="tc_submit" type="button" style="display:none;" onclick="$(\'.tagcategorysettings:nth-of-type('.$tc_block_index.') .tc_delete\').show(); $(this).hide();">Delete</button>
                <button class="tc_delete" type="submit" style="display:none;" name="tc_setting_status" value="delete">Really, really delete</button>
                <input type="text" name="tc_setting_id" style="display:none" value="'.$row['id'].'">
            </form>
            </div>';
        }

        // new
        $tag_id= 'tagme';
        $setting_type = 1;
        $html .= '
        <div class="tagcategoryblock">
        '.make_form(make_link("tags/categories")).'
            <table>
            <tr>
                <td>Controlling tag</td>
                <td>
                    <input type="text" name="tc_setting_tag" value="'.$tag_id.'">
                </td>
            </tr>
            <tr>
                <td>Type</td>
                <td>
                    <select name="tc_setting_type" id="setting_type">
                        <option value="0" '.($setting_type == 0 ? 'selected' : '' ).'>set hide</option>
                        <option value="1" '.($setting_type == 1 ? 'selected' : '' ).'>set show</option>
                        <option value="2" '.($setting_type == 2 ? 'selected' : '' ).'>make checkbox</option>
                        <option value="3" '.($setting_type == 3 ? 'selected' : '' ).'>make radio</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>affected tags</td>
                <td>
                    <textarea type="text" name="tc_setting_tag_list" class="autocomplete_tags" placeholder="tagme" rows="5" cols="15"  ></textarea>
                </td>
            </tr>
            </table>
            <button class="tc_submit" type="submit" name="tc_setting_status" value="new">Submit</button>
        </form>
        </div>

        </div>';
        */

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

    public function show_count_tag_categories(Page $page)
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
        foreach($dict as $dic){
            $label = $labels[$i++];
            $temphtml = "<div><h2>$label</h2><table class='table-odd noborders'><tr><th>tag</th><th>count</th><tr>";
            foreach($dic as $row){
                $temphtml .= "<tr><td>".$row["tag"]."</td><td>".$row["count"]."</td></tr>";
            }
            $temphtml .= "</table></div>";
            $html->appendChild(rawHTML($temphtml));
        }
        $page->set_title("Tag Categories counts");
        $page->set_heading("Tag Categories counts");
        
        $page->add_block(new Block("Tag Categories counts", DIV(["style" => "display:flex;justify-content:space-evenly;"],$html), "main", 10));
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
