<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomImageTheme extends Themelet
{
    public function display_random(Post $image): void
    {
        Ctx::$page->add_block(new Block("Random Post", $this->build_thumb($image, "_rand"), "left", 8));
    }
}
