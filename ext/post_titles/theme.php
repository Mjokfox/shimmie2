<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT,TD};

class PostTitlesTheme extends Themelet
{
    public function get_title_set_html(string $title, bool $can_set): HTMLElement
    {
        return SHM_POST_INFO(
            "Title",
            $title,
            $can_set ? INPUT(["type" => "text", "name" => "title", "value" => $title]) : null
        );
    }

    public function get_upload_specific_html(int|string $suffix): HTMLElement
    {
        return TD(
            INPUT([
                "type" => "text",
                "name" => "title{$suffix}",
                "value" => ($suffix == 0) ? @$_GET['title'] : null,
            ])
        );
    }
}
