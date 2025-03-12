<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{H3, H1, TR, TABLE, TH, TBODY, THEAD, DIV, A, BR, emptyHTML, rawHTML};

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

        $this->display_paginator($page, "forum/view/".$threadID, null, $pageNumber, $totalPages);

        $page->set_title($threadTitle);


        if ($user->can(ForumPermission::FORUM_CREATE)) {
            $html->appendChild($this->build_postbox($threadID));
        }

        if ($user->can(ForumPermission::FORUM_ADMIN)) {
            $html->appendChild($this->add_actions_block_custom($page, $threadID));
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
        $h_comment = $tfe->formatted;

        $h_name = html_escape($post["user_name"]);

        $i_post_id = $post["id"];

        $h_posted = autodate($post["date"]);

        $duser = User::by_name($post["user_name"]);
        $h_userlink = "<a class='username' href='".make_link("user/$h_name")."'>$h_name</a><br>{$duser->class->name}";
        /** @var BuildAvatarEvent $avatar_e */
        $avatar_e = send_event(new BuildAvatarEvent($duser));
        $h_avatar = $avatar_e->html;
        $h_del = "";
        if ($user->can(ForumPermission::FORUM_ADMIN)) {
            $h_del = A(["href" => make_link("forum/delete/".$threadID."/".$post['id'])], "Delete");
        }
        $h_edit = "";
        if ($user->can(CommentPermission::DELETE_COMMENT) || ($user->can(CommentPermission::CREATE_COMMENT) && $user->id === $duser->id)) {
            $h_edit = $this->edit_button($threadID, $i_post_id);
        }
        return rawHTML("
            <table class='comment' id=\"$i_post_id\"><tr>
                <td class='meta'>$h_userlink<br>$h_avatar<br/>$h_posted $h_del</td>
                <td class='c_body'>$h_comment<br><br><div class='c-actions'>$h_edit</div></td>
            </tr></table>
        ");

    }

    protected function build_postbox(int $threadID): HTMLElement
    {
        global $config;

        $max_characters = $config->get_int(ForumConfig::MAX_CHARS_PER_POST);
        return rawHTML('
		<div class="comment comment_add" id="cadd'.$threadID.'">
			'.make_form(make_link("forum/answer")).'
                Max characters allowed: '.$max_characters.'
				<input type="hidden" name="threadID" value="'.$threadID.'" />
				<textarea id="comment_on_'.$threadID.'" name="message" rows="5" cols="50"></textarea>
				<br><input type="submit" value="Reply" />
			</form>
		</div>
		');
    }

    public function add_actions_block_custom(Page $page, int $threadID): HTMLElement
    {
        return DIV(
            H3("Admin Actions"),
            A(["href" => make_link("forum/nuke/".$threadID)], "Delete this thread and its posts.")
        );
    }

    protected function edit_button(int $threadID, int $postID): string
    {
        return "<a class=\"c-edit\" onclick='forum_edit_box(this,$threadID, $postID)'>Edit</a>";
    }
}
