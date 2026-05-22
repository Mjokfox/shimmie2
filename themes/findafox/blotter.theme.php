<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, SPAN, emptyHTML};

/**
 * @phpstan-type BlotterEntry array{id:int,entry_date:string,entry_text:string,important:bool}
 */
class CustomBlotterTheme extends BlotterTheme
{
    /**
     * @param BlotterEntry[] $entries
     */
    public function display_blotter_page(array $entries): void
    {
        $i_color = Ctx::$config->get(BlotterConfig::COLOR);

        $html = emptyHTML();
        foreach ($entries as $entry) {
            $clean_date = date("Y/m/d", \Safe\strtotime($entry['entry_date']));
            $entry_text = $entry['entry_text'];
            $msg = emptyHTML("$clean_date: ", format_text($entry_text));
            if ($entry['important']) {
                $msg = SPAN(["style" => "color: $i_color;"], $msg);
            }
            $html->appendChild($msg, BR(), BR());
        }

        Ctx::$page->set_title("Blotter");
        Ctx::$page->add_block(new Block("Blotter Entries", $html, "main", 10));
    }
    /**
     * @param BlotterEntry[] $entries
     */
    public function display_blotter(array $entries): void
    {
        $count = \count($entries);
        if ($count === 1) {
            $entry = $entries[0];
            $id = $entry['id'];
            $removed = Ctx::$page->get_cookie("blotter-removed");
            if ((int)$removed >= $id) {
                return;
            }

            $messy_date = $entry['entry_date'];
            $clean_date = date("m/d/y", \Safe\strtotime($messy_date));
            $clean_time = SHM_DATE($messy_date);
            $html = DIV(
                ["class" => "blotter", "data-id" => $id],
                DIV(
                    ["class" => "shm-toggler blotter-container", "data-toggle-sel" => ".blotter-content"],
                    SPAN(
                        ["class" => "blotter-title"],
                        "Server news {$clean_date} (",
                        $clean_time,
                        ") Click to Show/Hide"
                    ),
                    DIV(["class" => "blotter-content", "style" => "display: none;"], format_text($entry['entry_text']))
                ),
                DIV(
                    ["class" => "blotter-tools"],
                    A(["href" => make_link("blotter/list")], "Show All"),
                    A(["href" => "#", "id" => "blotter-dismiss"], "Dismiss")
                ),
            );
            Ctx::$page->add_block(new Block(null, $html, "main", 0, "blotter", false));
        } else {
            parent::display_blotter($entries);
        }
    }
}
