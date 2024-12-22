<?php

declare(strict_types=1);

namespace Shimmie2;

class MarkdownInfo extends ExtensionInfo
{
    public const KEY = "markdown";

    public string $key = self::KEY;
    public string $name = "Markdown";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Turns markdown into HTML";
    public ?string $documentation =
        "  Basic formatting tags:
   <ul>
     <li>**<b>bold</b>**
     <li>*<i>italic</i>*
     <li>__<u>underline</u>__
     <li>~~<s>strikethrough</s>~~
     <li>^<sup>superscript</sup>^
     <li>_<sub>subscript</sub>_
     <li># Heading 1
     <li>## Heading 2
     <li>### Heading 3
     <li>#### Heading 4
     <li>[align=left|center|right]Aligned Text[/align]
   </ul>
   <br>
   Link tags:
   <ul>
     <li>!url (image)
     <li>site://images/image.jpg
     <li><a href=\"{self::SHIMMIE_URL}\">https://code.shishnet.org/</a>
     <li>[some text](<a href=\"{self::SHIMMIE_URL}\">https://code.shishnet.org/</a>)
     <li>site://ext_doc/bbcode
     <li>[Link to BBCode docs](site://ext_doc/bbcode)
     <li><a href=\"mailto:{self::SHISH_EMAIL}\">mailto:webmaster@shishnet.org</a>
     <li>wiki:wiki_article (add ! in front to keep wiki:)
     <li>&gt;&gt;123 (link to post #123)
     <li>[anchor=target]Scroll to #bb-target[/anchor]
   </ul>
   <br>
   More format Tags:
   <ul>
     <li>[list]Unordered list[/list]
     <li>[ul]Unordered list[/ul]
     <li>[ol]Ordered list[/ol]
     <li>[li]List Item[/li]
     <li>* list item (*, - or + works)
     <li>1. Ordered list item
     <li>[code]<pre><code>print(\"Hello World!\");</code></pre>[/code]
     <li>||<span class=\"spoiler\" title=\"spoilered text\" onclick=\"markdown_spoiler(this);\">Voldemort is bad</span>|| (spoiler)
     <li>> <blockquote><small>To be or not to be...</small>
     <li>>(Shakespeare) <blockquote><em>Shakespeare said:</em><br><small>... That is the question</small></blockquote>
   </ul>";
}
