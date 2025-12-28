<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, H1, H3, INPUT, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

/**
 * @phpstan-type Thread array{id:int,title:string,sticky:bool,user_name:string,uptodate:string,response_count:int}
 * @phpstan-type Post array{id:int,user_name:string,user_class:string,date:string,message:string}
 */
class CustomForumTheme extends ForumTheme
{
    /**
     * @param array<Post> $posts
     */
    public function display_thread(array $posts, string $threadTitle, int $threadID, int $pageNumber, int $totalPages): void
    {
        global $config, $page, $user;

        $tbody = TBODY();
        foreach ($posts as $post) {
            $tbody->appendChild($this->post_to_html($post, $threadID));
        }

        $html = emptyHTML(
            DIV(
                ["id" => "returnLink"],
                A(["href" => make_link("forum/index")], "Return")
            ),
            BR(),
            BR(),
            H1($threadTitle),
            BR(),
            TABLE(
                ["id" => "threadPosts", "class" => "zebra"],
                THEAD(
                    TR(
                        TH(["id" => "threadHeadUser"], "User"),
                        TH("Message")
                    )
                ),
                $tbody
            )
        );

        $this->display_paginator("forum/view/$threadID", null, $pageNumber, $totalPages);

        $page->set_title($threadTitle);


        if ($user->can(ForumPermission::FORUM_CREATE)) {
            $html->appendChild($this->build_postbox($threadID));
        }

        if ($user->can(ForumPermission::FORUM_ADMIN)) {
            $html->appendChild($this->add_actions_block_custom($threadID));
        }

        $page->add_block(new Block(null, $html, "main", 20));

    }

    /**
     * @param array{id:int,user_name:string,date:string,message:string} $post
     */
    protected function post_to_html(array $post, int $threadID): HTMLElement
    {
        global $user, $cache;

        $tfe = send_event(new TextFormattingEvent($post["message"]));
        $h_comment = $tfe->getFormattedHTML();

        $h_name = html_escape($post["user_name"]);

        $h_posted = SHM_DATE($post["date"]);

        $duser = User::by_name($post["user_name"]);
        $h_userlink = emptyHTML(A(["class" => "username", "href" => make_link("user/$h_name")], $h_name), BR(), $duser->class->name);
        /** @var BuildAvatarEvent $BAE */
        $BAE = send_event(new BuildAvatarEvent($duser));
        $h_avatar = $BAE->html;
        $h_del = null;
        if ($user->can(ForumPermission::FORUM_ADMIN)) {
            $h_del = SHM_SIMPLE_FORM(
                make_link("forum/delete/$threadID/" . $post['id']),
                SHM_SUBMIT("Delete"),
            );
        }
        $h_edit = null;
        if ($user->can(CommentPermission::DELETE_COMMENT) || ($user->can(CommentPermission::CREATE_COMMENT) && $user->id === $duser->id)) {
            $h_edit = $this->edit_button($threadID, $post["id"]);
        }
        return TABLE(
            ["class" => "comment", "id" => $post["id"]],
            TR(
                TD(["class" => "meta"], $h_userlink, BR(), $h_avatar, br(), $h_posted, $h_del),
                TD(["class" => "c_body"], $h_comment, BR(), BR(), $h_edit)
            )
        );

    }

    protected function build_postbox(int $threadID): HTMLElement
    {
        global $config;
        $max_characters = $config->get(ForumConfig::MAX_CHARS_PER_POST);
        return DIV(
            ["class" => "comment comment_add", "id" => "cadd$threadID"],
            SHM_SIMPLE_FORM(
                make_link("forum/answer"),
                "Max characters allowed: $max_characters ",
                INPUT(["type" => "hidden", "name" => "threadID", "value" => $threadID]),
                TEXTAREA(["id" => "comment_on_$threadID", "class" => "formattable", "name" => "message", "rows" => 5, "cols" => 50]),
                SHM_SUBMIT("Reply")
            )
        );
    }

    public function add_actions_block_custom(int $threadID): HTMLElement
    {
        return DIV(
            H3("Admin Actions"),
            A(["href" => make_link("forum/nuke/".$threadID)], "Delete this thread and its posts.")
        );
    }

    protected function edit_button(int $threadID, int $postID): HTMLElement
    {
        return A(["class" => "c-edit", "onclick" => "forum_edit_box(this,$threadID,$postID);"], " Edit");
    }
}
