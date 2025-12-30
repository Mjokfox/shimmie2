<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, INPUT, SPAN};

use MicroHTML\HTMLElement;

class NotificationsTheme extends Themelet
{
    /**
     * @param Notification[] $notifs
     */
    public function display_notifs(array $notifs): void
    {
        if (empty($notifs)) {
            Ctx::$page->add_block(new Block("Notifications", DIV(['class' => 'notif-main'], 'No notifications to display!')));
            return;
        }
        $bulk_actions = DIV(
            ['class' => 'notif-BA'],
            SHM_SIMPLE_FORM(
                make_link("notifications/read_all"),
                SHM_SUBMIT("Read all")
            ),
            SHM_SIMPLE_FORM(
                make_link("notifications/delete_all"),
                SHM_SUBMIT("Delete all")
            )
        );
        $container = DIV(['class' => 'notif-container']);
        foreach ($notifs as $n) {
            $from = User::by_id_dangerously_cached($n->from_id);
            switch ($n->type) {
                case Notification::TYPE_NEW_COMMENT_ON_POST:
                    $container->appendChild($this->notification_row(
                        SPAN(
                            ['class' => $n->is_read ? 'notif-read' : 'notif-unread'],
                            'User ',
                            A(['href' => make_link("user/$from->name")], "@$from->name"),
                            ' made a new comment on your post ',
                            A(['href' => make_link("notif/read/$n->id", query: new QueryArray(['r' => "post/view/$n->ref_id"]), fragment: "c$n->ref_id2")], ">>$n->ref_id"),
                            '!'
                        ),
                        $n->id,
                        $n->date
                    ));
                    break;
                case Notification::TYPE_MENTION_COMMENT:
                    $container->appendChild($this->notification_row(
                        SPAN(
                            ['class' => $n->is_read ? 'notif-read' : 'notif-unread'],
                            'User ',
                            A(['href' => make_link("user/$from->name")], "@$from->name"),
                            ' mentioned you on post ',
                            A(['href' => make_link("notif/read/$n->id", query: new QueryArray(['r' => "post/view/$n->ref_id"]), fragment: "c$n->ref_id2")], ">>$n->ref_id"),
                            '!'
                        ),
                        $n->id,
                        $n->date
                    ));
                    break;
                case Notification::TYPE_NEW_COMMENT_ON_FORUM:
                    $container->appendChild($this->notification_row(
                        SPAN(
                            ['class' => $n->is_read ? 'notif-read' : 'notif-unread'],
                            'User ',
                            A(['href' => make_link("user/$from->name")], "@$from->name"),
                            ' made a new comment on your forum thread ',
                            A(['href' => make_link("notif/read/$n->id", query: new QueryArray(['r' => "forum/view/$n->ref_id"]), fragment: "$n->ref_id2 ")], (string)$n->ref_id),
                            '!'
                        ),
                        $n->id,
                        $n->date
                    ));
                    break;
                case Notification::TYPE_MENTION_FORUM:
                    $container->appendChild($this->notification_row(
                        SPAN(
                            ['class' => $n->is_read ? 'notif-read' : 'notif-unread'],
                            'User ',
                            A(['href' => make_link("user/$from->name")], "@$from->name"),
                            ' mentioned you on a forum thread ',
                            A(['href' => make_link("notif/read/$n->id", query: new QueryArray(['r' => "forum/view/$n->ref_id"]), fragment: "$n->ref_id2 ")], (string)$n->ref_id),
                            '!'
                        ),
                        $n->id,
                        $n->date
                    ));
                    break;
                default:
                    break;
            }
        }
        $container->appendChild(
            DIV(
                ['class' => 'notif-actions'],
                INPUT(['type' => 'submit', 'value' => 'Read', 'name' => 'action']),
                INPUT(['type' => 'submit', 'value' => 'Delete', 'name' => 'action'])
            )
        );
        $action_form = SHM_SIMPLE_FORM(
            make_link("notifications/action"),
            $container
        );
        Ctx::$page->add_block(new Block("Notifications", DIV(['class' => 'notif-main'], $bulk_actions, $action_form)));
        Ctx::$page->set_title("notifications");
    }

    private function notification_row(string|HTMLElement $content, int $id, string $date): HTMLElement
    {
        return DIV(
            ['class' => 'notif-row'],
            DIV(INPUT(['type' => 'checkbox', 'name' => 'sel[]', 'value' => $id])),
            DIV(['class' => 'notif-row-content'], $content, DIV(['class' => 'notif-date'], SHM_DATE($date)))
        );
    }
}
