<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

/**
 * @phpstan-type BlotterEntry array{id:int,entry_date:string,entry_text:string,important:bool}
 */
class CustomBlotterTheme extends BlotterTheme
{
    /**
     * @param BlotterEntry[] $entries
     */
    public function display_blotter(array $entries): void
    {
        global $page, $config;
        $html = $this->get_html_for_blotter($entries);
        $page->add_block(new Block(null, rawHTML($html), "main", 1, "blotter"));
    }

    /**
     * @param BlotterEntry[] $entries
     */
    private function get_html_for_blotter(array $entries): string
    {
        global $config;

        $count = count($entries);
        if ($count === 1) {
            $entry = $entries[0];
            $messy_date = $entry['entry_date'];
            $clean_date = date("m/d/y", \Safe\strtotime($messy_date));
            $cleaner_time = SHM_DATE($messy_date);
            $out_text = "Server news: {$clean_date} ($cleaner_time)";
            $in_text = $entry['entry_text'];
            $id = $entry['id'];
            $html = "
            <div class='blotter' data-id='$id' style='display:none;'>
                <a href='#' id='blotter2-toggle' class='shm-blotter2-toggle' style='margin-left:auto;'>
                    <div id='blotter1' class='shm-blotter1'>
                        <span>$out_text Click to Show/Hide</span>
                    </div>
                    <div id='blotter2' class='shm-blotter2' style='display:none;'>$in_text</div>
                </a>
                <span>
                    <a href='".make_link("blotter/list")."'>Show All</a>
                    
                </span>
                <span class='shm-blotter-hide'>
                    <a href='#' id='blotter-hide'>Hide forever</a>
                </span>
            </div>
		    ";
        } else {
            $i_color = $config->get_string(BlotterConfig::COLOR, "#FF0000");
            $position = $config->get_string(BlotterConfig::POSITION, "subheading");
            $entries_list = "";
            foreach ($entries as $entry) {
                /**
                 * Blotter entries
                 */
                // Reset variables:
                $i_open = "";
                $i_close = "";
                //$id = $entry['id'];
                $messy_date = $entry['entry_date'];
                $clean_date = date("m/d/y", \Safe\strtotime($messy_date));
                $entry_text = $entry['entry_text'];
                if ($entry['important'] == 'Y') {
                    $i_open = "<span style='color: #$i_color'>";
                    $i_close = "</span>";
                }
                $entries_list .= "<li>{$i_open}{$clean_date} - {$entry_text}{$i_close}</li>";
            }

            $pos_break = "";
            $pos_align = "text-align: right; position: absolute; right: 0px;";

            if ($position === "left") {
                $pos_break = "<br />";
                $pos_align = "";
            }

            if ($count === 0) {
                $out_text = "No blotter entries yet.";
                $in_text = "Empty.";
            } else {
                $clean_date = date("m/d/y", \Safe\strtotime($entries[0]['entry_date']));
                $out_text = "Blotter updated: {$clean_date}";
                $in_text = "<ul>$entries_list</ul>";
            }
            $html = "
			<div id='blotter1' class='shm-blotter1'>
				<span>$out_text</span>
				{$pos_break}
				<span style='{$pos_align}'>
					<a href='#' id='blotter2-toggle' class='shm-blotter2-toggle'>Show/Hide</a>
					<a href='".make_link("blotter/list")."'>Show All</a>
				</span>
			</div>
			<div id='blotter2' class='shm-blotter2'>$in_text</div>
		";
        }
        return $html;
    }
}
