<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        $tc = new TagCategories();
        $_POST = ['tc_status' => "new", 'tc_category' => "artist", 'tc_up_group' => "artist", 'tc_lo_group' => "artist", 'tc_tag_list' => "bob", 'tc_color' => "#888888", "tc_up_type" => 0, "tc_up_prio" => 0];
        $tc->page_update();
        self::assertEquals("artist", TagCategories::get_tag_category("bob"));

        self::assertEquals(null, TagCategories::get_tag_category("alice"));

        $_POST = ['tc_status' => "edit", 'tc_category' => "artist", 'tc_up_group' => "artist", 'tc_lo_group' => "artist", 'tc_tag_list' => "alice joe", 'tc_color' => "#888888", "tc_up_type" => 0, "tc_up_prio" => 0];
        $tc->page_update();

        // self::assertEquals(null, TagCategories::get_tag_category("bob")); // issue with static variables
        // self::assertEquals("artist", TagCategories::get_tag_category("alice"));

        self::assertEquals(null, TagCategories::get_tag_category("notacategory:bob"));
    }
}
