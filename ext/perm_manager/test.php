<?php

declare(strict_types=1);

namespace Shimmie2;

class PermManagerTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        self::log_in_as_admin();
        self::get_page('perm_manager');
        self::assert_title("User Classes");
    }
}
