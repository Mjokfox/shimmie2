<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        $tc = new TagCategories();
        $_POST = ['action' => "new", 'name' => "artist", 'tags' => "bob", 'color' => "#888888", "priority" => "0"];
        $tc->page_update(new QueryArray($_POST));
        self::assertNotNull(TagCategories::get_category_info_for_tag("bob"));

        self::assertSame(null, TagCategories::get_category_info_for_tag("alice"));

        $_POST = ['action' => "edit", "id" => "4", 'name' => "artist", 'tags' => "alice joe", 'color' => "#888888", "priority" => "0"];
        $tc->page_update(new QueryArray($_POST));

        self::assertSame(null, TagCategories::get_category_info_for_tag("bob"));
        self::assertNotNull(TagCategories::get_category_info_for_tag("alice"));
    }
}
