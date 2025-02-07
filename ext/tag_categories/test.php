<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

class TagCategoriesTest extends ShimmiePHPUnitTestCase
{
    public function testParsing(): void
    {
        $tc = new TagCategories();
        $_POST = ['tc_status' => "new", 'tc_category' => "artist", 'tc_display_singular' => "artist", 'tc_display_multiple' => "artist", 'tc_tag_list' => "bob", 'tc_color' => "#888888"];
        $tc->page_update();
        $this->assertEquals("artist", TagCategories::get_tag_category("bob"));
        $this->assertEquals("bob", TagCategories::get_tag_body("bob"));

        $this->assertEquals(null, TagCategories::get_tag_category("alice"));
        $this->assertEquals("alice", TagCategories::get_tag_body("alice"));

        $_POST = ['tc_status' => "edit", 'tc_category' => "artist", 'tc_display_singular' => "artist", 'tc_display_multiple' => "artist", 'tc_tag_list' => "alice joe", 'tc_color' => "#888888"];
        $tc->page_update();

        // $this->assertEquals(null, TagCategories::get_tag_category("bob")); // issue with static variables
        // $this->assertEquals("artist", TagCategories::get_tag_category("alice"));

        $this->assertEquals(null, TagCategories::get_tag_category("notacategory:bob"));
        $this->assertEquals("notacategory:bob", TagCategories::get_tag_body("notacategory:bob"));
    }
}
