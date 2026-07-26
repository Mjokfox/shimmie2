<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReducedSizeImagesTest extends ShimmiePHPUnitTestCase
{
    public function testLoadData(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->create_post("tests/pbx_screenshot.jpg", "AC/DC");
        $image = Post::by_id_ex($image_id_1);

        self::assertException(PermissionDenied::class, function () {
            self::get_page("_images/feb01bab5698a11dd87416724c7a89e3/1%20-%20ACDC.jpg");
        });

        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertSame(
            "/test/_reduced_images/feb01bab5698a11dd87416724c7a89e3/1%20-%20ACDC.jpg",
            (string)$image->get_media_link()
        );
        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertSame(
            "/test/index.php?q=reduced_image%2F1%2F1%2520-%2520ACDC.jpg",
            (string)$image->get_media_link()
        );

        self::log_in_as_admin();

        self::get_page("_images/feb01bab5698a11dd87416724c7a89e3/1%20-%20ACDC.jpg");
        self::assert_response(200);

        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertSame(
            "/test/_images/feb01bab5698a11dd87416724c7a89e3/1%20-%20ACDC.jpg",
            (string)$image->get_media_link()
        );
        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertSame(
            "/test/index.php?q=image%2F1%2F1%2520-%2520ACDC.jpg",
            (string)$image->get_media_link()
        );
    }
}
