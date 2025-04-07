<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, INPUT, emptyHTML};

use MicroHTML\HTMLElement;

class CustomhomeTheme extends HomeTheme
{
    protected function build_search(): HTMLElement
    {
        global $config, $user;
        $search_html = DIV(
            ["class" => "space search-bar", "id" => "search"],
            SHM_FORM(
                action: search_link(),
                method: "GET",
                children: [
                    INPUT(["name" => "search", "size" => "30", "type" => "text", "placeholder" => "tag search", "class" => "autocomplete_tags", "autofocus" => true]),
                    " ",
                    SHM_SUBMIT("Search")
                ]
            )
        );
        if (ReverseImageInfo::is_enabled() && $config->get(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
            $search_html->appendChild(A(["href" => "#", "onclick" => "$(\".search-bar\").toggle();"], "Or describe an image"));
            $search_html = emptyHTML(
                $search_html,
                DIV(
                    ["class" => "space search-bar", "id" => "text-search", "style" => "display:none;"],
                    SHM_FORM(
                        action: make_link("post/search"),
                        method: "GET",
                        children: [
                            INPUT(["name" => "search", "size" => "30", "type" => "text", "placeholder" => "text search", "class" => "autocomplete_tags"]),
                            " ",
                            SHM_SUBMIT("Search")
                        ]
                    ),
                    A(["href" => "#", "onclick" => "$(\".search-bar\").toggle();"], "Back to tag search")
                )
            );
        }
        return $search_html;
    }
}
