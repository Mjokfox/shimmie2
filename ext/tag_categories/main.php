<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "config.php";

class TagCategories extends Extension
{
    /** @var TagCategoriesTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        // whether we split out separate categories on post view by default
        //  note: only takes effect if /post/view shows the image's exact tags
        $config->set_default_bool(TagCategoriesConfig::SPLIT_ON_VIEW, true);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version(TagCategoriesConfig::VERSION) < 1) {
            // primary extension database, holds all our stuff!
            $database->create_table(
                'image_tag_categories',
                'id SCORE_AIPK,
                category VARCHAR(60),
				display_singular VARCHAR(60),
				display_multiple VARCHAR(60),
				color VARCHAR(7)'
            );

            $database->create_table(
                'image_tag_categories_tags',
                'category_id INTEGER,
                tag_id INTEGER,
                FOREIGN KEY(category_id) REFERENCES image_tag_categories(id),
                FOREIGN KEY(tag_id) REFERENCES tags(id),
                PRIMARY KEY (category_id, tag_id)'
            );
            /*
            $database->create_table(
                'image_tag_categories_settings',
                'id INTEGER,
                tag_id INTEGER,
                setting_type INTEGER,
                FOREIGN KEY(tag_id) REFERENCES tags(id),
                PRIMARY KEY(id AUTOINCREMENT)'
            );
            $database->create_table(
                'image_tag_categories_settings_tags',
                'setting_id INTEGER,
                tag_id INTEGER,
                FOREIGN KEY(setting_id) REFERENCES image_tag_categories_settings(id),
                FOREIGN KEY(tag_id) REFERENCES tags(id),
                PRIMARY KEY (setting_id, tag_id)'
            );
            */

            $this->set_version(TagCategoriesConfig::VERSION, 1);

            log_info("tag_categories", "extension installed");
        }

        // if empty, add our default values
        $number_of_db_rows = $database->execute('SELECT COUNT(*) FROM image_tag_categories;')->fetchColumn();

        if ($number_of_db_rows == 0) {
            $database->execute(
                'INSERT INTO image_tag_categories (category, display_singular, display_multiple, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "artist", "single" => "Artist", "multiple" => "Artists", "color" => "#BB6666"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories (category, display_singular, display_multiple, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "series", "single" => "Series", "multiple" => "Series", "color" => "#AA00AA"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories (category, display_singular, display_multiple, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "character", "single" => "Character", "multiple" => "Characters", "color" => "#66BB66"]
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("tag_categories", new Link('tags/categories'), "Tag Categories", NavLink::is_active(["tag_categories"]));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        if ($event->page_matches("tags/categories", method: "GET")) {
            $this->theme->show_tag_categories($page, $database->get_all('SELECT * FROM image_tag_categories'));
        }
        else if ($event->page_matches("tags/categories", method: "POST", permission: Permissions::EDIT_TAG_CATEGORIES)) {
            $this->page_update();
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tags/categories"));
        }
        else if ($event->page_matches("admin/count_categories_tags", method: "GET")){
            $this->theme->show_count_tag_categories($page);
        }
        else if ($event->page_matches("admin/count_categories_tags", method: "POST")){
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("admin/count_categories_tags"));
        }
    }

    /*public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^(.+)tags([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])([0-9]+)$/i", $event->term, $matches)) {
            global $database;
            $type = strtolower($matches[1]);
            $cmp = ltrim($matches[2], ":") ?: "=";
            $count = $matches[3];

            $types = $database->get_col(
                'SELECT LOWER(category) FROM image_tag_categories'
            );
            if (in_array($type, $types)) {
                $event->add_querylet(
                    new Querylet("(
					    SELECT count(distinct t.id)
					    FROM tags t
					    INNER JOIN image_tags it ON it.tag_id = t.id AND images.id = it.image_id
					    WHERE LOWER(t.tag) LIKE LOWER('$type:%')) $cmp $count
					")
                );
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Tag Categories", $this->theme->get_help_html());
        }
    }*/

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_form();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch($event->action) {
            case "count_categories_tags":
                $event->redirect = false;
                break;
        }
    }

    /**
     * @return array<string, array{category: string, display_singular: string, display_multiple: string, color: string}>
     */
    public static function getKeyedDict(): array
    {
        global $database;
        static $tc_keyed_dict = null;

        if (is_null($tc_keyed_dict)) {
            $tc_keyed_dict = [];
            $tc_dict = $database->get_all('SELECT * FROM image_tag_categories');

            foreach ($tc_dict as $row) {
                $tc_keyed_dict[(string)$row['category']] = $row;
            }
        }

        return $tc_keyed_dict;
    }
    public static function getCategorizedTags(): array
    {
        global $database;
        static $tc_category_dict = null;
            if ($tc_category_dict === null){
            $query = "
            SELECT c.category, t.tag
            FROM image_tag_categories_tags ct
            JOIN image_tag_categories c ON ct.category_id = c.id
            JOIN tags t ON ct.tag_id = t.id;
            ";

            $tc_dict = $database->get_all($query);

            foreach ($tc_dict as $row) {
                $tc_category_dict[$row['tag']] = $row["category"];
            }
        }
        return $tc_category_dict;
    }

    public static function get_tag_category(string $tag): ?string
    {
        $tag_category_dict = static::getCategorizedTags();
        if (array_key_exists($tag,$tag_category_dict)){
            return $tag_category_dict[$tag];
        }
        return null;
    }

    public static function get_tag_body(string $tag): string
    {
        return $tag;
    }

    public static function getTagHtml(string $h_tag, string $extra_text = ''): string
    {
        $h_tag_no_underscores = str_replace("_", " ", $h_tag);

        $tag_category_dict = static::getKeyedDict();

        // we found a tag, see if it's valid!
        $tag_category_dict = static::getCategorizedTags();
        if (array_key_exists($h_tag,$tag_category_dict)){
            $category = $tag_category_dict[$h_tag];
            $tag_category_css = ' tag_category_'.$category;
            $tag_category_style = 'style="color:'.html_escape($tag_category_dict[$category]['color']).';" ';
            $h_tag_no_underscores = str_replace("_", " ", $h_tag);

            $h_tag_no_underscores = '<span class="'.$tag_category_css.'" '.$tag_category_style.'>'.$h_tag_no_underscores.$extra_text.'</span>';
        } else {
            $h_tag_no_underscores .= $extra_text;
        }

        return $h_tag_no_underscores;
    }

    private function add_tags_to_category($category,$tags) :void
    {
        global $database;
        $tags = str_replace("\n",' ', $tags);
        $tags = Tag::explode($tags, false);
        $tag_ids = [];
        foreach ($tags as $tag){$tag_ids[] = Tag::get_or_create_id($tag);}

        $query = "
        SELECT id
        FROM image_tag_categories
        WHERE category = :category;";
        $args = ["category" => $category];

        $category_id = $database->get_one($query, $args);

        $query = "
        INSERT INTO image_tag_categories_tags (category_id, tag_id)
        VALUES (:category_id, :tag_id);";
        $args = ["category_id" => $category_id];
        foreach($tag_ids as $tag){
            $args["tag_id"] = $tag;
            $database->execute($query,$args);
        }
    }
    private function delete_tags_from_category($category) :void
    {
        global $database;
        $database->execute(
            'DELETE FROM image_tag_categories_tags
            WHERE category_id = (
                SELECT id
                FROM image_tag_categories
                WHERE category = :category
        );',
        [
            'category' => $category
        ]
        );
    }
    /*
    private function add_tags_to_setting($id,$tags) :void
    {
        global $database;
        $tags = str_replace("\n",' ', $tags);
        $tags = Tag::explode($tags, false);
        $tag_ids = [];
        foreach ($tags as $tag){$tag_ids[] = Tag::get_or_create_id($tag);}

//         $query = "
//         SELECT id
//         FROM image_tag_categories_settings
//         WHERE id = :id;";
//         $args = ["id" => $id];
//
//         $category_id = $database->get_one($query, $args);

        $query = "
        INSERT INTO image_tag_categories_settings_tags (setting_id, tag_id)
        VALUES (:setting_id, :tag_id);";
        $args = ["setting_id" => $id];
        foreach($tag_ids as $tag){
            $args["tag_id"] = $tag;
            $database->execute($query,$args);
        }
    }
    private function delete_tags_from_setting($id) :void
    {
        global $database;
        $database->execute(
            'DELETE FROM image_tag_categories_settings_tags
            WHERE setting_id = :id;',
        [
            'id' => $id
        ]
        );
    }
    */

    public function page_update(): void
    {
        global $user, $database;
        if (isset($_POST['tc_status'])){
            if (!isset($_POST['tc_status']) and
            !isset($_POST['tc_category']) and
            !isset($_POST['tc_display_singular']) and
            !isset($_POST['tc_display_multiple']) and
            !isset($_POST['tc_tag_list']) and
            !isset($_POST['tc_color'])) {
                return;
            }

            if ($_POST['tc_status'] == 'edit') {
                $database->execute(
                    'UPDATE image_tag_categories
                    SET display_singular=:display_singular,
                        display_multiple=:display_multiple,
                        color=:color
                    WHERE category=:category',
                    [
                        'category' => $_POST['tc_category'],
                        'display_singular' => $_POST['tc_display_singular'],
                        'display_multiple' => $_POST['tc_display_multiple'],
                        'color' => $_POST['tc_color'],
                    ]
                );
                $this->delete_tags_from_category($_POST['tc_category']);
                $this->add_tags_to_category($_POST['tc_category'],$_POST['tc_tag_list']);

            } elseif ($_POST['tc_status'] == 'new') {
                $database->execute(
                    'INSERT INTO image_tag_categories (category, display_singular, display_multiple, color)
                    VALUES (:category, :display_singular, :display_multiple, :color)',
                    [
                        'category' => $_POST['tc_category'],
                        'display_singular' => $_POST['tc_display_singular'],
                        'display_multiple' => $_POST['tc_display_multiple'],
                        'color' => $_POST['tc_color'],
                    ]
                );
                $this->add_tags_to_category($_POST['tc_category'],$_POST['tc_tag_list']);
            } elseif ($_POST['tc_status'] == 'delete') {
                $this->delete_tags_from_category($_POST['tc_category']);
                $database->execute(
                    'DELETE FROM image_tag_categories
                    WHERE category=:category',
                    [
                        'category' => $_POST['tc_category']
                    ]
                );
            }
        } /*else {
            if (!isset($_POST['tc_setting_status']) and
                // !isset($_POST['tc_setting_id']) and
                !isset($_POST['tc_setting_tag']) and
                !isset($_POST['tc_setting_type']) and
                !isset($_POST['tc_setting_tag_list'])) {
                return;
                }
                if ($_POST['tc_setting_status'] == 'edit') {
                    $database->execute(
                        'UPDATE image_tag_categories_settings
                        SET setting_type=:setting_type,
                        tag_id=:tag_id
                        WHERE id=:id',
                        [
                            'setting_type' => $_POST['tc_setting_type'],
                            'tag_id' => Tag::get_or_create_id($_POST['tc_setting_tag']),
                            'id' => $_POST['tc_setting_id'],
                        ]
                    );
                    $this->delete_tags_from_setting($_POST['tc_setting_id']);
                    $this->add_tags_to_setting($_POST['tc_setting_id'],$_POST['tc_setting_tag_list']);

                } elseif ($_POST['tc_setting_status'] == 'new') {
                    $database->execute(
                        'INSERT INTO image_tag_categories_settings (tag_id, setting_type)
                        VALUES (:tag_id, :setting_type)',
                                       [
                                           'tag_id' => Tag::get_or_create_id($_POST['tc_setting_tag']),
                                       'setting_type' => $_POST['tc_setting_type'],
                                       ]
                    );
                    $setting_id = $database->get_one("SELECT MAX(id) AS id FROM image_tag_categories_settings");
                    $this->add_tags_to_setting($setting_id,$_POST['tc_setting_tag_list']);
                } elseif ($_POST['tc_setting_status'] == 'delete') {
                    $this->delete_tags_from_setting($_POST['tc_setting_id']);
                    $database->execute(
                        'DELETE FROM image_tag_categories_settings
                        WHERE id=:id',
                        [
                            'id' => $_POST['tc_setting_id']
                        ]
                    );
                }
        }*/
    }
}
