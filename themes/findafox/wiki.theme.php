<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, DIV, rawHTML, emptyHTML};

class CustomWikiTheme extends WikiTheme
{
    public function display_page(Page $page, WikiPage $wiki_page, ?WikiPage $nav_page = null): void
    {
        global $user;

        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $tfe = send_event(new TextFormattingEvent($nav_page->body));

        // only the admin can edit the sidebar
        if ($user->can(Permissions::WIKI_ADMIN)) {
            $tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar/edit")."'>Edit</a>)";
        }

        // see if title is a category'd tag
        $title_html = html_escape($wiki_page->title);

        if (!$wiki_page->exists) {
            $page->set_code(404);
        }

        $page->set_title(html_escape($wiki_page->title));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Wiki Index", rawHTML($tfe->formatted), "left", 20));
        $page->add_block(new Block("Recent wiki changes", $this->get_recent_changes(), "left", 21));
        $page->add_block(new Block($title_html, $this->create_display_html($wiki_page)));
    }

    public function get_recent_changes(): HTMLElement
    {
        global $database;
        $data = $database->get_all(
            "SELECT title, date
            FROM wiki_pages
            WHERE id IN (
                SELECT MAX(id)
                FROM wiki_pages
                GROUP BY title
            )
            ORDER BY id DESC
            LIMIT 10;
        "
        );
        if (count($data) < 1) {
            return DIV("No recent wiki changes");
        }

        $html = emptyHTML();
        $time = time();
        $i = 0;
        foreach ($data as $row) {
            $date = $row["date"];
            if ($time - strtotime($date) > 2678400) {
                break;
            } // one month
            $i++;
            $title = $row["title"];
            $html->appendChild(
                A(
                    ["href" => make_link("wiki/$title")],
                    ucfirst($title)
                ),
                rawHTML(" ".autodate($date)),
                BR(),
            );
        }
        return $i > 0 ? $html : DIV("No recent wiki changes");
    }
}
