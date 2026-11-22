<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\SPAN;

/**
 * @extends Extension<TagCategoriesTheme>
 */
final class TagCategories extends Extension
{
    public const KEY = "tag_categories";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

        if ($this->get_version() < 1) {
            $database->create_table(
                'image_tag_categories',
                'id SCORE_AIPK,
				name VARCHAR(60),
				color CHAR(7),
                priority INTEGER NOT NULL',
            );

            $database->create_table(
                'image_tag_categories_tags',
                'id SCORE_AIPK,
                category_id INTEGER,
                tag_id INTEGER,
                FOREIGN KEY(category_id) REFERENCES image_tag_categories(id) ON DELETE CASCADE,
                FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE'
            );

            $database->execute("CREATE INDEX idx_itct_category_id ON image_tag_categories_tags(category_id);");
            $database->execute("CREATE INDEX idx_itct_tag_id ON image_tag_categories_tags(tag_id);");
            $database->execute("CREATE INDEX itc_name_idx ON image_tag_categories(name)");

            $this->set_version(5);
        }
        if ($this->get_version() < 2) {
            $database->execute("ALTER TABLE image_tag_categories RENAME COLUMN display_singular to name;");
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS display_multiple;");
            $database->execute("ALTER TABLE image_tag_categories ADD COLUMN id SCORE_AIPK;");
            $database->execute("ALTER TABLE image_tag_categories ADD COLUMN priority INTEGER;");
            $database->execute("ALTER TABLE image_tag_categories ADD CONSTRAINT image_tag_categories_category_key UNIQUE (category);");
            $this->set_version(2);
        }

        if ($this->get_version() < 3) {
            $database->execute("CREATE INDEX idx_itct_category_id ON image_tag_categories_tags(category_id);");
            $database->execute("CREATE INDEX idx_itct_tag_id ON image_tag_categories_tags(tag_id);");
            $this->set_version(4);
        }

        if ($this->get_version() < 4) {
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS upper_group;");
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS lower_group;");
            $this->set_version(4);
        }

        if ($this->get_version() < 5) { // tag categories -> upload
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS category");
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS upload_group");
            $database->execute("ALTER TABLE image_tag_categories DROP COLUMN IF EXISTS upload_page_type");
            $database->execute("ALTER TABLE image_tag_categories RENAME COLUMN list_group to name");
            $database->execute("ALTER TABLE image_tag_categories RENAME COLUMN upload_page_priority to priority");

            $database->execute("ALTER TABLE image_tag_categories_tags DROP CONSTRAINT image_tag_categories_tags_category_id_fkey");
            $database->execute("ALTER TABLE image_tag_categories_tags DROP CONSTRAINT image_tag_categories_tags_tag_id_fkey");

            $database->execute("ALTER TABLE image_tag_categories_tags ADD FOREIGN KEY(category_id) REFERENCES image_tag_categories(id) ON DELETE CASCADE");
            $database->execute("ALTER TABLE image_tag_categories_tags ADD FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE");

            $database->execute("DROP INDEX IF EXISTS idx_itc_upload_group");

            $database->execute("CREATE INDEX itc_name_idx ON image_tag_categories(name)");

            $this->set_version(5);
        }


        // if empty, add our default values
        $number_of_db_rows = $database->get_one('SELECT COUNT(*) FROM image_tag_categories');

        if ($number_of_db_rows === 0) {
            $query = "INSERT INTO image_tag_categories (name, color, priority) VALUES (:name, :color, :priority)";
            $database->execute($query, ["name" => "Artist", "color" => "#BB6666", "priority" => 100]);
            $database->execute($query, ["name" => "Series", "color" => "#AA00AA", "priority" => 50]);
            $database->execute($query, ["name" => "Character", "color" => "#66BB66", "priority" => 10]);
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('tags/categories'), "Tag Categories", ["tag_categories"]);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tags/categories", method: "GET")) {
            $this->theme->show_tag_categories();
        } elseif ($event->page_matches("tags/categories", method: "POST", permission: TagCategoriesPermission::EDIT_TAG_CATEGORIES)) {
            $this->page_update($event->POST);
            Ctx::$page->set_redirect(make_link("tags/categories"));
        } elseif ($event->page_matches("tags/categories_tags", method: "GET")) {
            $this->theme->show_count_tag_categories();
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_form();
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "count_categories_tags":
                $event->redirect = false;
                Ctx::$page->set_redirect(make_link("tags/categories_tags"));
                break;
        }
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^(.+?)_?tags(:|<=|<|=|>|>=)([0-9]+)$/i")) {
            $name = strtolower($matches[1]);
            $cmp = cmp_escape($matches[2]);
            $count = $matches[3];

            $exists = Ctx::$database->get_one(
                'SELECT 1 FROM image_tag_categories
                WHERE LOWER(name) = LOWER(:name)',
                ['name' => $name]
            );

            if (!\is_null($exists)) {
                $event->add_querylet(
                    new Querylet("
					    EXISTS (
                            SELECT 1
                            FROM image_tags it
                            INNER JOIN image_tag_categories_tags itct ON it.tag_id = itct.tag_id AND itct.category_id IN (
                                SELECT id FROM image_tag_categories
                                WHERE name ILIKE :name
                            )
                            AND images.id = it.image_id
                            HAVING count(it.image_id) $cmp :count
                        )
					", ["name" => $name, "count" => $count])
                );
            }
        }
    }


    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Tag Categories", $this->theme->get_help_html());
        }
    }

    /**
     * @return array{id: int, name: string, color: string, priority: int}[]
     */
    public static function get_all_categories(): array
    {
        // @phpstan-ignore return.type
        return Ctx::$database->get_all('SELECT * FROM image_tag_categories ORDER BY priority DESC');
    }

    /**
     * @return ?array{name: string, color: string, priority: int}
     */
    public static function get_category_info_for_tag(string $tag): ?array
    {
        static $category_info = null;
        if ($category_info === null || defined('UNITTEST')) {
            $res = Ctx::$database->get_all(
                "SELECT t.tag, c.*
                FROM image_tag_categories_tags ct
                JOIN image_tag_categories c ON ct.category_id = c.id
                JOIN tags t ON ct.tag_id = t.id"
            );

            $category_info = [];
            foreach ($res as $row) {
                $category_info[$row["tag"]] = $row;
            }
        }

        return \array_key_exists($tag, $category_info) ? $category_info[$tag] : null;
    }

    public static function getTagHtml(string $tag): HTMLElement
    {
        $clean_tag = str_replace("_", " ", $tag);

        $category_info = TagCategories::get_category_info_for_tag($tag);
        if (!is_null($category_info)) {
            return SPAN(
                [
                    "class" => "tag_category_" . $category_info['name'],
                    "style" => "color:".$category_info['color'].";"
                ],
                $clean_tag
            );

        }
        return SPAN($clean_tag);
    }

    public function page_update(QueryArray $post): void
    {
        $action = $post->req("action");
        switch ($action) {
            case 'new':
                $name = $post->req("name");
                $color = $post->req("color");
                $priority = $post->req("priority");
                $tags = $post->req("tags");
                Ctx::$database->execute(
                    'INSERT INTO image_tag_categories (name, color, priority) VALUES (:name, :color, :priority)',
                    ["name" => $name, "color" => $color, "priority" => $priority],
                );
                $id = Ctx::$database->get_last_insert_id('image_tag_categories_id_seq');
                $this->add_tags_to_category($id, $tags);
                Log::info("tag_categories", "Created category: $name", "Created category: $name");
                break;
            case 'edit':
                $id = int_escape($post->req("id"));
                $name = $post->req("name");
                $color = $post->req("color");
                $priority = $post->req("priority");
                $tags = $post->req("tags");
                Ctx::$database->execute(
                    'UPDATE image_tag_categories
                    SET name=:name, color=:color, priority=:priority
                    WHERE id=:id',
                    ["id" => $id, "name" => $name, "color" => $color, "priority" => $priority],
                );
                Ctx::$database->execute(
                    'DELETE FROM image_tag_categories_tags WHERE category_id = :id;',
                    ['id' => $id]
                );
                $this->add_tags_to_category($id, $tags);
                Log::info("tag_categories", "Edited category: $name", "Edited category: $name");
                break;
            case 'delete':
                $id = $post->req("id");
                $name = $post->req("name");
                Ctx::$database->execute('DELETE FROM image_tag_categories WHERE id=:id', ['id' => $id]);
                Log::info("tag_categories", "Deleted category: $name", "Deleted category: $name");
                break;
            default:
                throw new InvalidInput("Action $action not valid");
        }
    }

    private function add_tags_to_category(int $category_id, string $tags): void
    {
        $tags = str_replace("\n", ' ', $tags);
        $tags = Tag::sanitize_array(explode(' ', trim($tags)));
        $tag_ids = [];
        foreach ($tags as $tag) {
            $tag_ids[] = Tag::get_or_create_id($tag);
        }

        foreach ($tag_ids as $tag_id) {
            Ctx::$database->execute(
                "INSERT INTO image_tag_categories_tags (category_id, tag_id) VALUES (:category_id, :tag_id);",
                ["category_id" => $category_id, "tag_id" => $tag_id]
            );
        }
    }
}
