<?php

declare(strict_types=1);

namespace Shimmie2;

class FeaturedTheme extends Themelet
{
    public function display_featured(Post $image): void
    {
        Ctx::$page->add_block(new Block("Featured Post", $this->build_thumb($image, "_feat"), "left", 3));
    }
}
