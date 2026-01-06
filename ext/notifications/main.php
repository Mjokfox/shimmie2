<?php

declare(strict_types=1);

namespace Shimmie2;

final class Notification
{
    public int $id = -1;
    public mixed $date;
    public function __construct(
        public int $user_id,
        public int $from_id,
        public int $type,
        public ?int $ref_id,
        public ?int $ref_id2,
        public bool $is_read = false
    ) {
    }

    public const int TYPE_MENTION_COMMENT = 1;
    public const int TYPE_MENTION_FORUM = 2;
    public const int TYPE_NEW_COMMENT_ON_POST = 10;
    public const int TYPE_NEW_COMMENT_ON_FORUM = 11;

    /**
     * @param array{
     *     id: string|int,
     *     user_id: string|int,
     *     from_id: string|int,
     *     type: string,
     *     reference_id: string,
     *     reference_id2: string|bool,
     *     is_read: string|int,
     *     date: string
     * } $row
     */
    public static function from_row(array $row): Notification
    {
        $n = new Notification(
            (int)$row["user_id"],
            (int)$row["from_id"],
            (int)$row["type"],
            (int)$row["reference_id"],
            (int)$row["reference_id2"],
            bool_escape($row["is_read"]),
        );
        $n->id = (int)$row["id"];
        $n->date = $row["date"];
        return $n;
    }
}

/** @extends Extension<NotificationsTheme> */
final class Notifications extends Extension
{
    public const KEY = "notifications";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            Ctx::$database->create_table("notifications", "
				id SCORE_AIPK,
                user_id INTEGER NOT NULL,
				from_id INTEGER NOT NULL,
				date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				type INTEGER NOT NULL,
                reference_id INTEGER,
                reference_id2 INTEGER,
				is_read BOOLEAN NOT NULL DEFAULT FALSE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE
			");
            Ctx::$database->execute("CREATE INDEX notifications__user_id ON notifications(user_id)");
            $this->set_version(1);
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        if (!Ctx::$user->is_anonymous()) {
            $count = $this->count_notifs(Ctx::$user);
            if ($count > 0) {
                $event->add_nav_link(make_link("notifications"), "Notifications ($count)", order:11);
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if (Ctx::$user->is_anonymous()) {
            return;
        } // anonymous doesnt get notifications

        if ($event->page_matches("notifications")) {
            $notifications = Ctx::$database->get_all('SELECT * FROM notifications WHERE user_id = :user_id', ['user_id' => Ctx::$user->id]);
            $this->theme->display_notifs(array_map(fn ($e) => Notification::from_row($e), $notifications)); // @phpstan-ignore-line
        } elseif ($event->page_matches("notif/read/{id}")) { // read notification, and forward to the right page from the GET query 'r'
            $r = $event->GET->req('r');
            Ctx::$database->execute('UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id', ['id' => $event->get_iarg('id'), 'user_id' => Ctx::$user->id]);
            Ctx::$page->set_redirect(make_link($r));
            Ctx::$cache->delete('notif-count-'.Ctx::$user->id);
        } elseif ($event->page_matches("notifications/action", method: "POST", authed:true)) {// from form, delete or read, go back to /notifications
            $this->action($event->POST);
            Ctx::$page->set_redirect(make_link('notifications'));
        } elseif ($event->page_matches("notifications/read_all", authed:true)) {// set all to is_read, go back to /notifications
            Ctx::$database->execute('UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id', ['user_id' => Ctx::$user->id]);
            Ctx::$cache->delete('notif-count-'.Ctx::$user->id);
            Ctx::$page->flash('Set all notifications to read');
            Ctx::$page->set_redirect(make_link('notifications'));
        } elseif ($event->page_matches("notifications/delete_all", authed:true)) {// delete all, go to user page
            Ctx::$database->execute('DELETE FROM notifications WHERE user_id = :user_id', ['user_id' => Ctx::$user->id]);
            Ctx::$cache->delete('notif-count-'.Ctx::$user->id);
            Ctx::$page->flash('Delete all notifications');
            Ctx::$page->set_redirect(make_link('user'));
        }
    }

    public function onCommentPosting(CommentPostingEvent $event): void
    {
        // For the post owner
        $post = Image::by_id($event->image_id);
        if (!is_null($post) && $event->user->id !== $post->owner_id) {
            $this->create_notification(new Notification(
                $post->owner_id,
                $event->user->id,
                Notification::TYPE_NEW_COMMENT_ON_POST,
                $event->image_id,
                $event->comment_id,
            ));
        }

        // @ mentions in the comment
        preg_match_all('/@(\S+)/m', $event->comment, $matches);
        if (count($matches[1]) < 1) {
            return;
        }
        $res = array_unique($matches[1]);
        $k = array_search($event->user->name, $res, true); // no need to notify yourself
        if ($k !== false) {
            unset($res[$k]);
        }

        foreach ($res as $name) {
            try {
                $user = User::by_name($name);
                $this->create_notification(new Notification(
                    $user->id,
                    $event->user->id,
                    Notification::TYPE_MENTION_COMMENT,
                    $event->image_id,
                    $event->comment_id,
                ));
            } catch (UserNotFound $e) {
                // username does not exist
            }
        }
    }

    private function action(QueryArray $POST): void
    {
        $action = $POST->req('action');
        $ids = $POST->getAll('sel');
        if (empty($ids)) {
            return;
        }
        $count = \count($ids);
        if ($count > 1) {
            $condition = 'IN (' . implode(',', $ids) . ')';
            $args = ['user_id' => Ctx::$user->id];
        } else {
            $condition = "= :id";
            $args = ['id' => $ids[0], 'user_id' => Ctx::$user->id];
        }
        switch ($action) {
            case 'Delete':
                $q = new Querylet("DELETE FROM notifications WHERE id $condition AND user_id = :user_id", $args);
                $action .= "d";
                break;
            case 'Read':
                $q = new Querylet("UPDATE notifications SET is_read = TRUE WHERE id $condition AND user_id = :user_id", $args);
                break;
            default:
                throw new UserError('invalid action');
        }
        Ctx::$database->execute($q->sql, $q->variables); // @phpstan-ignore-line
        Ctx::$cache->delete("notif-count-" . Ctx::$user->id);
        Ctx::$page->flash("$action $count notification" . ($count > 1 ? 's.' : '.'));
    }

    private function create_notification(Notification $notif): void
    {
        Ctx::$database->execute(
            'INSERT INTO notifications(user_id, from_id, type, reference_id, reference_id2)
            VALUES(:user_id, :from_id, :type, :reference_id, :reference_id2)',
            ['user_id' => $notif->user_id,
             'from_id' => $notif->from_id,
             'type' => $notif->type,
             'reference_id' => $notif->ref_id,
             'reference_id2' => $notif->ref_id2,
            ]
        );
    }

    private function count_notifs(User $user): int
    {
        return cache_get_or_set(
            "notif-count-$user->id",
            fn () => (int)Ctx::$database->get_one("
                SELECT count(*)
                FROM notifications
                WHERE user_id = :user_id
                AND is_read = :is_read
            ", ["user_id" => $user->id, "is_read" => false]),
            30
        );
    }
}
