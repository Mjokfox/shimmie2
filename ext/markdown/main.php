<?php

declare(strict_types=1);

namespace Shimmie2;

class Markdown extends FormatterExtension
{
    public function format(string $text): string
    {
        return "<span class='markdown'>$text</span>";
    }

    public function strip(string $text): string
    {
        $types = ["\*\*\*","\*\*","\*","__","_","~~","```"]; // bi, b, i, u, i, s, code
        foreach ($types as $el) {
            $text = preg_replace_ex("!$el(.*?)$el!", "$1", $text);
        }

        $types = ["\#\#\#\#","\#\#\#","\#\#","\#",]; // h1, 2, 3, 4
        foreach ($types as $el) {
            $text = preg_replace_ex("!^$el (.+)!m", "$1<", $text);
        }

        $text = preg_replace_ex('/!?\[(.+?)\]\(((?:https?|ftp|irc|mailto|site):\/\/[^\s|[]+)\)/s', '$2', $text);
        $text = preg_replace_ex("!\[anchor=(.*?)\](.*?)\[/anchor\]!s", '$2', $text);
        $text = preg_replace_ex("!\[/?(list|ul|ol)\]!", "", $text);
        $text = preg_replace_ex("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "$2", $text);
        $text = preg_replace_ex('/\|\|(.*?)\|\|/s', '$1', $text); // spoiler
        return $text;
    }
}
