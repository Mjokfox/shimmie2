<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, emptyHTML};

use MicroHTML\HTMLElement;

class CustomWikiTheme extends WikiTheme
{
    public function display_page(WikiPage $wiki_page, ?WikiPage $nav_page = null): void
    {
        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $tfe = send_event(new TextFormattingEvent($nav_page->body));

        // only the admin can edit the sidebar
        if (Ctx::$user->can(WikiPermission::ADMIN)) {
            $nav = emptyHTML($tfe->getFormattedHTML(), BR(), A(["href" => make_link("wiki/wiki:sidebar/edit")], "Edit"));
        } else {
            $nav = $tfe->getFormattedHTML();
        }

        // see if title is a category'd tag
        $title_html = html_escape($wiki_page->title);

        if (!$wiki_page->exists) {
            Ctx::$page->set_code(404);
        }

        Ctx::$page->set_title(html_escape($wiki_page->title));
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Wiki Index", $nav, "left", 20));
        Ctx::$page->add_block(new Block("Recent wiki changes", $this->get_recent_changes(), "left", 21));
        Ctx::$page->add_block(new Block($title_html, $this->create_display_html($wiki_page)));
    }

    public function display_list_page(?WikiPage $nav_page = null): void
    {
        if (is_null($nav_page)) {
            $nav_page = new WikiPage();
            $nav_page->body = "";
        }

        $body_html = format_text($nav_page->body);

        $query = "SELECT DISTINCT title FROM wiki_pages
                ORDER BY title ASC";
        $titles = Ctx::$database->get_col($query);
        $html = DIV(["class" => "wiki-all-grid"]);
        foreach ($titles as $title) {
            $html->appendChild(DIV(A(["href" => make_link("wiki/$title")], $title)));
        }
        Ctx::$page->set_title("Wiki page list");
        Ctx::$page->add_block(new Block("Wiki Index", $body_html, "left", 20));
        Ctx::$page->add_block(new Block("Recent wiki changes", $this->get_recent_changes(), "left", 21));
        Ctx::$page->add_block(new Block("All Wiki Pages", $html));
    }

    public function get_recent_changes(): HTMLElement
    {
        $data = Ctx::$database->get_all(
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
                    ucfirst("$title ")
                ),
                SHM_DATE($date),
                BR(),
            );
        }
        return $i > 0 ? $html : DIV("No recent wiki changes");
    }
}
