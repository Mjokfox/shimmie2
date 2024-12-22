<?php

declare(strict_types=1);

namespace Shimmie2;

class Markdown extends FormatterExtension
{
    public function format(string $text): string
    {
        $text = $this->_format($text);
        return "<span class='markdown'>$text</span>";
    }

    public function _format(string $text): string
    {
        $text = $this->extract_code($text);
        $text = preg_replace_ex("!\*\*\*(.*?)\*\*\*!", "<b><i>$1</b></i>", $text); // bi
        $types = ["\*\*","\*","__","_","~~","\^"]; // b, i, u, sub, s, sup
        $replacements = ["b","i","u","sub","s","sup"];
        foreach ($types as $i => $el) {
            $r = $replacements[$i];
            $text = preg_replace_ex("!$el(.*?)$el!", "<$r>$1</$r>", $text);
        }
        $types = ["\#\#\#\#","\#\#\#","\#\#","\#",]; // h1, 2, 3, 4
        $replacements = ["h4","h3","h2","h1"];
        foreach ($types as $i => $el) {
            $r = $replacements[$i];
            $text = preg_replace_ex("!^$el\s(.+)!m", "<$r>$1</$r>", $text);
        }
        $text = preg_replace_ex('/^&gt;\((\S+)\)\s+(.+)/m', "<blockquote><i><b>$1</b> said:</i><br><small>$2</small></blockquote>", $text);
        $text = preg_replace_ex('/^&gt;\s+(.+)/m', '<blockquote><small>$1</small></blockquote>', $text);
        $text = preg_replace_ex('/&gt;&gt;(\d+)(#c?\d+)?/s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('post/view/$1$2').'">&gt;&gt;$1$2</a>', $text);
        $text = preg_replace_ex('/(?<!{{LINKPLACEHOLDER}})!\[(.+?)\]\(((?:https?|ftp|irc|mailto|site):\/\/[^\s|[]+)\)/s', '<img alt="{{LINKPLACEHOLDER}}$1" src="{{LINKPLACEHOLDER}}$2">', $text); // image
        $text = preg_replace_ex('/(?<!{{LINKPLACEHOLDER}})\[(.+?)\]\(((?:https?|ftp|irc|mailto|site):\/\/[^\s|[]+)\)/s', '<a href="{{LINKPLACEHOLDER}}$2">{{LINKPLACEHOLDER}}$1</a>', $text); // []()
        $text = preg_replace_ex('/(?<!{{LINKPLACEHOLDER}})!((?:https?|ftp|irc|mailto|site):\/\/\S+)/s', '<img alt="user image" src="{{LINKPLACEHOLDER}}$1">', $text); // image
        $text = preg_replace_ex('/(?<!{{LINKPLACEHOLDER}})((?:https?|ftp|irc|mailto|site):\/\/\S+)/s', '<a href="{{LINKPLACEHOLDER}}$1">{{LINKPLACEHOLDER}}$1</a>', $text);
        $text = preg_replace_ex('/site:\/\/(\S+)/s',make_link('$1'),$text);
        $text = str_replace('{{LINKPLACEHOLDER}}', '', $text);
        $text = preg_replace_ex('!\[anchor=(.*?)\](.*?)\[/anchor\]!s', '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>', $text);  // add "bb-" to avoid clashing with eg #top
        $text = preg_replace_ex('/(^|[^\!])wiki:(\S+)/s', '$1<a href="'.make_link('wiki/$1').'">$2</a>', $text);
        $text = preg_replace_ex('/\!wiki:(\S+)/s', '<a href="'.make_link('wiki/$1').'">wiki:$1</a>', $text);
        $text = preg_replace_ex("!^(?:\*|-|\+)\s(.*)!m", "<li>$1</li>", $text);
        $text = preg_replace_ex("!^(\d+)\.\s(.*)!m", "<ol start=\"$1\"><li>$2</li></ol>", $text);
        $text = preg_replace_ex("!\n\s*\n!", "\n\n", $text);
        $text = str_replace("\n", "\n<br>", $text);
        while (\Safe\preg_match("/\[list\](.*?)\[\/list\]/s", $text)) {
            $text = preg_replace_ex("/\[list\](.*?)\[\/list\]/s", "<ul>\\1</ul>", $text);
        }
        while (\Safe\preg_match("/\[ul\](.*?)\[\/ul\]/s", $text)) {
            $text = preg_replace_ex("/\[ul\](.*?)\[\/ul\]/s", "<ul>\\1</ul>", $text);
        }
        while (\Safe\preg_match("/\[ol\](.*?)\[\/ol\]/s", $text)) {
            $text = preg_replace_ex("/\[ol\](.*?)\[\/ol\]/s", "<ol>\\1</ol>", $text);
        }
        $text = preg_replace_ex("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", $text);
        $text = preg_replace_ex("#<br><(li|ul|ol|/ul|/ol)#s", "<\\1", $text);
        $text = preg_replace_ex("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "<div style='text-align:\\1;'>\\2</div>", $text);
        $text = preg_replace_ex('/\|\|(.*?)\|\|/s', '<span class="spoiler" title="spoilered text" onclick="markdown_spoiler(this);">$1</span>', $text);
        $text = $this->insert_code($text);
        return $text;
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

    private function extract_code(string $text): string
    {
        # at the end of this function, the only code! blocks should be
        # the ones we've added -- others may contain malicious content,
        # which would only appear after decoding
        $text = str_replace("[code!]", "```", $text);
        $text = str_replace("[/code!]", "```", $text);
        return preg_replace_callback("!```(.*?)```!s", function($matches) {return "[code!]".base64_encode(trim($matches[1]))."[/code!]";}, $text);
    }

    private function insert_code(string $text): string
    {
        return preg_replace_callback("~\[code!\](.*?)\[/code!\]~s", function($matches) {return "<pre><code>".base64_decode($matches[1])."</code></pre>";}, $text);
    }
}
