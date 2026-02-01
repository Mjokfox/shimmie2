<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\rawHTML;

/** @extends Extension<TagCategoriesTheme> */
final class TagCategories extends Extension
{
    public const KEY = "tag_categories";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            // primary extension database, holds all our stuff!
            $database->create_table(
                'image_tag_categories',
                'id SCORE_AIPK,
                category VARCHAR(60) UNIQUE,
				upper_group VARCHAR(60),
				lower_group VARCHAR(60),
				color VARCHAR(7),
                upload_page_type INTEGER,
                upload_page_priority INTEGER',
            );
            // upload_type: 0/NULL = hidden, 1 = half width, 2 = full width, 3 = single width, 4 = single full width row

            $database->create_table(
                'image_tag_categories_tags',
                'category_id INTEGER,
                id SCORE_AIPK,
                tag_id INTEGER,
                FOREIGN KEY(category_id) REFERENCES image_tag_categories(id),
                FOREIGN KEY(tag_id) REFERENCES tags(id)'
            );

            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->execute("ALTER TABLE image_tag_categories RENAME COLUMN display_singular to upper_group;");
            $database->execute("ALTER TABLE image_tag_categories RENAME COLUMN display_multiple to lower_group;");
            $database->execute("ALTER TABLE image_tag_categories ADD COLUMN upload_page_type INTEGER;");
            $database->execute("ALTER TABLE image_tag_categories ADD COLUMN upload_page_priority INTEGER;");
            $database->execute("ALTER TABLE image_tag_categories ADD CONSTRAINT image_tag_categories_category_key UNIQUE (category);");
            $this->set_version(2);
        }

        // if empty, add our default values
        $number_of_db_rows = $database->get_one('SELECT COUNT(*) FROM image_tag_categories');

        if ($number_of_db_rows === 0) {
            $database->execute(
                'INSERT INTO image_tag_categories (category, upper_group, lower_group, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "artist", "single" => "Artist", "multiple" => "Artists", "color" => "#BB6666"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories (category, upper_group, lower_group, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "series", "single" => "Series", "multiple" => "Series", "color" => "#AA00AA"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories (category, upper_group, lower_group, color) VALUES (:category, :single, :multiple, :color)',
                ["category" => "character", "single" => "Character", "multiple" => "Characters", "color" => "#66BB66"]
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('tags/categories'), "Tag Categories", ["tag_categories"]);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tags/categories", method: "GET")) {
            /** @var array<array{category: string, upper_group: string, lower_group: string, color: string, upload_page_type: ?int, upload_page_priority: ?int}> $tcs */
            $tcs = Ctx::$database->get_all('SELECT * FROM image_tag_categories ORDER BY upload_page_priority IS NULL, upload_page_priority DESC;');
            $this->theme->show_tag_categories($tcs);
        } elseif ($event->page_matches("tags/categories", method: "POST", permission: TagCategoriesPermission::EDIT_TAG_CATEGORIES)) {
            $this->page_update();
            Ctx::$page->set_redirect(make_link("tags/categories"));
        } elseif ($event->page_matches("admin/count_categories_tags", method: "GET")) {
            $this->theme->show_count_tag_categories();
        } elseif ($event->page_matches("admin/count_categories_tags", method: "POST")) {
            Ctx::$page->set_redirect(make_link("admin/count_categories_tags"));
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_form();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "count_categories_tags":
                $event->redirect = false;
                break;
        }
    }

    /**
     * @return array<string, array{category: string, upper_group: string, lower_group: string, color: string, upload_page_type: ?int, upload_page_priority: ?int}>
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
    /**
     * @return array{string:string}|null
     */
    public static function getCategorizedTags(): ?array
    {
        global $database;
        static $tc_category_dict = null;
        if ($tc_category_dict === null) {
            $query = "
            SELECT t.tag, c.category
            FROM image_tag_categories_tags ct
            JOIN image_tag_categories c ON ct.category_id = c.id
            JOIN tags t ON ct.tag_id = t.id
            ORDER BY ct.id;";

            $tc_category_dict = $database->get_pairs($query);
        }
        return $tc_category_dict;
    }

    public static function get_tag_category(string $tag): ?string
    {
        $tag_category_dict = static::getCategorizedTags();
        if (is_null($tag_category_dict)) {
            return null;
        }
        if (array_key_exists($tag, $tag_category_dict)) {
            return $tag_category_dict[$tag];
        }
        return null;
    }

    public static function getTagHtml(string $h_tag, string $extra_text = ''): HTMLElement
    {
        $h_tag_no_underscores = str_replace("_", " ", $h_tag);

        $keyed_dict = static::getKeyedDict();

        // we found a tag, see if it's valid!
        $tag_category_dict = static::getCategorizedTags();
        if (!is_null($tag_category_dict)) {
            if (array_key_exists($h_tag, $tag_category_dict)) {
                $category = $tag_category_dict[$h_tag];
                $tag_category_css = " tag_category_$category";
                $tag_category_style = 'style="color:'.html_escape($keyed_dict[$category]['color']).';" ';
                $h_tag_no_underscores = str_replace("_", " ", $h_tag);

                $h_tag_no_underscores = "<span class=\"$tag_category_css\"$tag_category_style>$h_tag_no_underscores$extra_text</span>";
            } else {
                $h_tag_no_underscores .= $extra_text;
            }
        }

        return rawHTML($h_tag_no_underscores);
    }

    private function add_tags_to_category(string $category, string $tags): void
    {
        global $database;
        $tags = str_replace("\n", ' ', $tags);
        $tags = Tag::explode($tags, false);
        $tag_ids = [];
        foreach ($tags as $tag) {
            $tag_ids[] = Tag::get_or_create_id($tag);
        }

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
        foreach ($tag_ids as $tag) {
            $args["tag_id"] = $tag;
            $database->execute($query, $args);
        }
    }
    private function delete_tags_from_category(string $category): void
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

    public function page_update(): void
    {
        global $database;
        if (isset($_POST['tc_status'])) {
            if (!isset($_POST['tc_category']) ||
            !isset($_POST['tc_up_group']) ||
            !isset($_POST['tc_lo_group']) ||
            !isset($_POST['tc_tag_list']) ||
            !isset($_POST['tc_color']) ||
            !isset($_POST['tc_up_type']) ||
            !isset($_POST['tc_up_prio'])) {
                return;
            }

            if ($_POST['tc_status'] === 'edit') {
                $database->execute(
                    'UPDATE image_tag_categories
                    SET upper_group=:upper_group,
                        lower_group=:lower_group,
                        color=:color,
                        upload_page_type=:upload_page_type,
                        upload_page_priority=:upload_page_priority
                    WHERE category=:category',
                    [
                        'category' => $_POST['tc_category'],
                        'upper_group' => $_POST['tc_up_group'],
                        'lower_group' => $_POST['tc_lo_group'],
                        'color' => $_POST['tc_color'],
                        'upload_page_type' => $_POST['tc_up_type'],
                        'upload_page_priority' => $_POST["tc_up_prio"],
                    ]
                );
                $this->delete_tags_from_category($_POST['tc_category']);
                $this->add_tags_to_category($_POST['tc_category'], $_POST['tc_tag_list']);
                Log::info("tag_categories", "Edited category: ".$_POST['tc_category'], "Edited category: ".$_POST['tc_category']);

            } elseif ($_POST['tc_status'] === 'new') {
                $database->execute(
                    'INSERT INTO image_tag_categories (category, upper_group, lower_group, color, upload_page_type, upload_page_priority)
                    VALUES (:category, :upper_group, :lower_group, :color, :upload_page_type, :upload_page_priority)',
                    [
                        'category' => $_POST['tc_category'],
                        'upper_group' => $_POST['tc_up_group'],
                        'lower_group' => $_POST['tc_lo_group'],
                        'color' => $_POST['tc_color'],
                        'upload_page_type' => $_POST['tc_up_type'],
                        'upload_page_priority' => $_POST["tc_up_prio"],
                    ]
                );
                $this->add_tags_to_category($_POST['tc_category'], $_POST['tc_tag_list']);
                Log::info("tag_categories", "Created category: ".$_POST['tc_category'], "Created category: ".$_POST['tc_category']);
            } elseif ($_POST['tc_status'] === 'delete') {
                $this->delete_tags_from_category($_POST['tc_category']);
                $database->execute(
                    'DELETE FROM image_tag_categories
                    WHERE category=:category',
                    [
                        'category' => $_POST['tc_category']
                    ]
                );
                Log::info("tag_categories", "Deleted category: ".$_POST['tc_category'], "Deleted category: ".$_POST['tc_category']);
            }
        }
    }
}
