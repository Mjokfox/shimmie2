<?php

declare(strict_types=1);

namespace Shimmie2;

class Markdown extends FormatterExtension
{
    public const KEY = "markdown";
    public function format(string $text): string
    {
        return "<span class='markdown'>$text</span>";
    }

    public function strip(string $text): string
    {
        $types = ["\*\*\*","\*\*","\*","__","_","~~","```"]; // bi, b, i, u, i, s, code
        foreach ($types as $el) {
            $text = \Safe\preg_replace("!$el(.*?)$el!", "$1", $text);
        }

        $types = ["\#\#\#\#","\#\#\#","\#\#","\#",]; // h1, 2, 3, 4
        foreach ($types as $el) {
            $text = \Safe\preg_replace("!^$el (.+)!m", "$1<", $text);
        }

        $text = \Safe\preg_replace('/!?\[(.+?)\]\(((?:https?|ftp|irc|mailto|site):\/\/[^\s|[]+)\)/s', '$2', $text);
        $text = \Safe\preg_replace("!\[anchor=(.*?)\](.*?)\[/anchor\]!s", '$2', $text);
        $text = \Safe\preg_replace("!\[/?(list|ul|ol)\]!", "", $text);
        $text = \Safe\preg_replace("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "$2", $text);
        $text = \Safe\preg_replace('/\|\|(.*?)\|\|/s', '$1', $text); // spoiler
        return $text;
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("api/internal/tag_count")) {
            $s = $event->GET->req('s');
            $count = cache_get_or_set("md-count-$s", fn () => Search::count_images(SearchTerm::explode($s)), 60);
            Ctx::$page->set_data(MimeType::HTML, (string)$count);
        }
    }
}
