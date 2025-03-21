<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

class WarmUploadTheme extends UploadTheme
{
    public function display_block(): void
    {
        global $page;
        $page->add_block(new Block("Upload", $this->build_upload_block(), "head", 20));
    }

    public function display_full(): void
    {
        global $page;
        $page->add_block(new Block("Upload", emptyHTML("Disk nearly full, uploads disabled"), "head", 20));
    }
}
