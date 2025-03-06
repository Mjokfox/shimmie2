<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{rawHTML, BODY};

class CustomhomeTheme extends HomeTheme
{
    public function build_body(string $sitename, string $main_links, string $main_text, string $contact_link, string $num_comma, string $counter_text): HTMLElement
    {
        global $page, $config, $user;
        $page->set_layout("front-page");

        $main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
        $message_html = empty($main_text) ? "" : "<div class='space' id='message'>$main_text</div>";
        $counter_html = empty($counter_text) ? "" : "<div class='space' id='counter'>$counter_text</div>";
        $contact_link = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a> &ndash;";
        $search_html = "
            <div class='space search-bar' id='search'>
				<form action='post/list' method='GET'>
				<input name='search' size='30' type='search' value='' placeholder='Search with tags' class='autocomplete_tags' autofocus='autofocus' />
				<input type='hidden' name='q' value='post/list'>
				<input type='submit' value='Search'/>
				</form>
                
		";
        if (ReverseImageInfo::is_enabled() && $config->get_bool(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get_bool(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
            $search_html .= "<a href='#' onclick='$(\".search-bar\").toggle();'>Or describe an image</a>
			</div>
			<div class='space search-bar' id='text-search' style='display:none'>
				<form action='post/search' method='GET'>
				<input name='search' size='30' type='search' value='' placeholder='Describe an image'/>
				<input type='hidden' name='q' value='post/search'>
				<input type='submit' value='Search'/>
				</form>
                <a href='#' onclick='$(\".search-bar\").toggle();'>Back to tag search</a>
			</div>";
        } else {
            $search_html .= "</div>";
        }
        return BODY(
            $page->body_attrs(),
            rawHTML("
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			$counter_html
			<div class='space' id='foot'>
				<small><small>
				$contact_link" . (empty($num_comma) ? "" : " Serving $num_comma posts &ndash;") . "
				Running <a href='https://code.shishnet.org/shimmie2/'>Shimmie2</a>
				</small></small>
			</div>
		</div>")
        );
    }
}
