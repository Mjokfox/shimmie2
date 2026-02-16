<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, H1, H3, INPUT, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

class CustomForumTheme extends ForumTheme
{
    /** @param ForumPost[] $posts */
    public function display_thread(ForumThread $thread, array $posts, bool $showAdminOptions, int $thread_id, int $pageNumber, int $totalPages): void
    {
        $tbody = TBODY();
        foreach ($posts as $post) {
            $tbody->appendChild($this->post_to_html($post, $thread_id));
        }

        $html = emptyHTML(
            DIV(
                ["id" => "returnLink"],
                A(["href" => make_link("forum/index")], "Return")
            ),
            BR(),
            BR(),
            H1($thread->title),
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

        $this->display_paginator("forum/view/$thread_id", null, $pageNumber, $totalPages);

        Ctx::$page->set_title($thread->title);


        if (Ctx::$user->can(ForumPermission::FORUM_CREATE)) {
            $html->appendChild($this->build_postbox($thread_id));
        }

        if (Ctx::$user->can(ForumPermission::FORUM_ADMIN)) {
            $html->appendChild($this->add_actions_block_custom($thread_id));
        }

        Ctx::$page->add_block(new Block(null, $html, "main", 20));
    }

    protected function post_to_html(ForumPost $post, int $thread_id): HTMLElement
    {
        $tfe = send_event(new TextFormattingEvent($post->message));
        $h_comment = $tfe->getFormattedHTML();

        $h_name = $post->owner->name;

        $h_posted = SHM_DATE($post->date);

        $duser = User::by_name($post->owner->name);
        $h_userlink = emptyHTML(A(["class" => "username", "href" => make_link("user/$h_name")], $h_name), BR(), $duser->class->name);
        /** @var BuildAvatarEvent $BAE */
        $BAE = send_event(new BuildAvatarEvent($duser));
        $h_avatar = $BAE->html;
        $h_del = null;
        if (Ctx::$user->can(ForumPermission::FORUM_ADMIN)) {
            $h_del = SHM_SIMPLE_FORM(
                make_link("forum/delete/$thread_id/$post->id"),
                SHM_SUBMIT("Delete"),
            );
        }
        $h_edit = null;
        if (Ctx::$user->can(CommentPermission::DELETE_COMMENT) || (Ctx::$user->can(CommentPermission::CREATE_COMMENT) && Ctx::$user->id === $duser->id)) {
            $h_edit = $this->edit_button($post->id, $thread_id, $post->message);
        }
        return TABLE(
            ["class" => "comment", "id" => "p{$post->id}"],
            TR(
                TD(["class" => "meta"], $h_userlink, BR(), $h_avatar, br(), $h_posted, $post->edited ? " (edited)" : null, $h_del),
                TD(["class" => "c_body", "id" => "$post->id"], $h_comment, BR(), BR(), $h_edit)
            )
        );

    }

    protected function build_postbox(int $thread_id): HTMLElement
    {
        $max_characters = Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST);
        return DIV(
            ["class" => "comment comment_add", "id" => "post_composer"],
            SHM_SIMPLE_FORM(
                make_link("forum/answer"),
                "Max characters allowed: $max_characters ",
                INPUT(["type" => "hidden", "name" => "thread_id", "value" => $thread_id]),
                TEXTAREA(["id" => "comment_on_$thread_id", "class" => "formattable", "name" => "message", "rows" => 5, "cols" => 50]),
                SHM_SUBMIT("Reply")
            )
        );
    }

    public function add_actions_block_custom(int $thread_id): HTMLElement
    {
        return DIV(
            H3("Admin Actions"),
            A(["href" => make_link("forum/nuke/$thread_id")], "Delete this thread and its posts.")
        );
    }

    public function display_new_post_composer(int $thread_id): void
    {

    }
}
