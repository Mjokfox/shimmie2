<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML,DIV,IMG,SPAN};

/**
 * @phpstan-type Tip array{id: int, image: string, text: string, enable: bool}
 */

class CustomtipsTheme extends TipsTheme
{
    /**
     * @param Tip $tip
     */
    public function showTip(string $url, array $tip): void
    {
        global $page;

        $html = DIV(
            ["id" => "tips"],
            rawHTML(format_text($tip['text'])),
            (
                empty($tip['image']) ? null :
                DIV(
                    ["class" => "tips-subcont"],
                    IMG(["src" => $url.url_escape($tip['image'])]),
                    SPAN("Tip!")
                )
            )
        );
        $page->add_block(new Block(null, $html, "left", 75));
    }
}
