<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSchedulingTest extends ShimmiePHPUnitTestCase
{
    public function testScheduling(): void
    {
        self::log_in_as_user();
        // queue is empty, uploads instantly
        $image_id = $this->create_post("tests/pbx_screenshot.jpg", "pbx computer screenshot", ["schedule" => "on"]);
        self::assertEquals(1, $image_id);

        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: computer pbx screenshot");

        // is queued
        $image_id = $this->create_post("tests/bedroom_workshop.jpg", "bedroom workshop computer", ["schedule" => "on"]);
        self::assertEquals(1, $image_id); // return slot (0) + 1
        self::assertStringContainsString("Scheduled bedroom_workshop.jpg;", implode(Ctx::$page->flash)); // just to be sure
    }
}
