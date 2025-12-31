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
                    $container->appendChild($this->notification_row('made a new comment on your post', $n->is_read, $from->name, $n->id, "post/view/$n->ref_id", "c$n->ref_id2", ">>$n->ref_id", $n->date));
                    break;
                case Notification::TYPE_MENTION_COMMENT:
                    $container->appendChild($this->notification_row('mentioned you on post', $n->is_read, $from->name, $n->id, "post/view/$n->ref_id", "c$n->ref_id2", ">>$n->ref_id", $n->date));
                    break;
                case Notification::TYPE_NEW_COMMENT_ON_FORUM:
                    $container->appendChild($this->notification_row('made a new comment on your forum thread', $n->is_read, $from->name, $n->id, "forum/view/$n->ref_id", "$n->ref_id2 ", (string)$n->ref_id, $n->date));
                    break;
                case Notification::TYPE_MENTION_FORUM:
                    $container->appendChild($this->notification_row('mentioned you on a forum thread', $n->is_read, $from->name, $n->id, "forum/view/$n->ref_id", "$n->ref_id2 ", (string)$n->ref_id, $n->date));
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
    /** @param (fragment-string&non-empty-string) $fragment */
    private function notification_row(string|HTMLElement $content, bool $read, string $from, int $id, string $view_link, string $fragment, string $link_text, string $date): HTMLElement
    {
        return
        DIV(
            ['class' => 'notif-row'],
            DIV(INPUT(['type' => 'checkbox', 'name' => 'sel[]', 'value' => $id])),
            DIV(
                ['class' => 'notif-row-content'],
                SPAN(
                    ['class' => $read ? 'notif-read' : 'notif-unread'],
                    'User ',
                    A(['href' => make_link("user/$from")], "@$from"),
                    " $content",
                    A(['href' => make_link("notif/read/$id", query: new QueryArray(['r' => $view_link]), fragment: $fragment)], $link_text),
                    '!'
                ),
                DIV(['class' => 'notif-date'], SHM_DATE($date))
            )
        );
    }
}
