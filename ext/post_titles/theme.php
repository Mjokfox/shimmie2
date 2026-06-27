<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, P, TD, emptyHTML};

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
                "maxlength" => "255",
                "value" => ($suffix === "0") ? @$_GET['title'] : null,
            ])
        );
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts having some string in their title"),
            SHM_COMMAND_EXAMPLE("title=words", "Returns posts who's title contains the string 'words', case insensitive."),
            SHM_COMMAND_EXAMPLE("title=any", "Returns posts with any title"),
            SHM_COMMAND_EXAMPLE("title=none", "Returns posts with no title"),
        );
    }
}
