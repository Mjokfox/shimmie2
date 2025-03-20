<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\A;
use function MicroHTML\DIV;
use function MicroHTML\IMG;
use function MicroHTML\SCRIPT;
use function MicroHTML\SELECT;
use function MicroHTML\SPAN;
use function MicroHTML\emptyHTML;

class CBZFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $page;
        $data_href = Url::base();
        $ilink = $image->get_image_link();
        $html = emptyHTML(
            DIV(
                ["id" => "comicMain"],
                DIV(
                    ["class" => "comicPager"],
                    SELECT(["id" => "comicPageList"])
                ),
                DIV(
                    ["id" => "comicView"],
                    A(["id" => "comicPrev"], SPAN("<")),
                    IMG(["alt" => "comic", "id" => "comicPage", "src" => "{$data_href}/ext/handle_cbz/spinner.gif"]),
                    A(["id" => "comicNext"], SPAN(">"))
                )
            ),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/jszip-utils.min.js"]),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/jszip.min.js"]),
            SCRIPT(["src" => "{$data_href}/ext/handle_cbz/comic.js"]),
            SCRIPT("window.comic = new Comic('comicMain', '$ilink');")
        );
        $page->add_block(new Block(null, $html, "main", 10, "comicBlock"));
    }
}
