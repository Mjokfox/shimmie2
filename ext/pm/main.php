<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;
use GQLA\Mutation;

use function MicroHTML\{emptyHTML, SPAN};

class SendPMEvent extends Event
{
    public PM $pm;
    public int $id;

    public function __construct(PM $pm)
    {
        parent::__construct();
        $this->pm = $pm;
    }
}

class EditPMEvent extends Event
{
    public PM $pm;

    public function __construct(PM $pm)
    {
        parent::__construct();
        $this->pm = $pm;
    }
}

#[Type(name: "PrivateMessage")]
class PM
{
    public int $id = -1;
    public int $from_id;
    public string $from_ip;
    public int $to_id;
    public mixed $sent_date;
    #[Field]
    public string $subject;
    #[Field]
    public string $message;
    #[Field]
    public bool $is_read;

    #[Field]
    public function from(): User
    {
        return User::by_id($this->from_id);
    }

    #[Field(name: "pm_id")]
    public function graphql_oid(): int
    {
        return $this->id;
    }
    #[Field(name: "id")]
    public function graphql_guid(): string
    {
        return "pm:{$this->id}";
    }

    public function __construct(
        int $from_id,
        string $from_ip,
        int $to_id,
        string $subject,
        string $message,
        bool $is_read = false
    ) {
        $this->from_id = $from_id;
        $this->from_ip = $from_ip;
        $this->to_id   = $to_id;
        $this->subject = $subject;
        $this->message = $message;
        $this->is_read = $is_read;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function from_row(array $row): PM
    {
        $pm = new PM(
            (int)$row["from_id"],
            $row["from_ip"],
            (int)$row["to_id"],
            $row["subject"],
            $row["message"],
            bool_escape($row["is_read"]),
        );
        $pm->id = (int)$row["id"];
        $pm->sent_date = $row["sent_date"];
        return $pm;
    }

    /**
     * @return PM[]|null
     */
    #[Field(extends: "User", name: "private_messages", type: "[PrivateMessage!]")]
    public static function get_pms_to(User $to, int $limit=null): ?array
    {
        global $database;

        $pms = [];
        $arr = $database->get_all(
            "SELECT * FROM private_message WHERE to_id = :to_id AND archived_by IS DISTINCT FROM :to_id AND archived_by IS DISTINCT FROM -1 ORDER BY sent_date DESC LIMIT :limit",
            ["to_id" => $to->id, "limit" => $limit]
        );
        foreach ($arr as $pm) {
            $pms[] = PM::from_row($pm);
        }
        return $pms;
    }

    /**
     * @return PM[]|null
     */
    #[Field(extends: "User", name: "private_messages", type: "[PrivateMessage!]")]
    public static function get_pms_by(User $by, int $limit=null): ?array
    {
        global $database;

        $pms = [];
        $arr = $database->get_all(
            "SELECT * FROM private_message WHERE from_id = :from_id AND archived_by IS DISTINCT FROM :from_id AND archived_by IS DISTINCT FROM -1 ORDER BY sent_date DESC LIMIT :limit",
            ["from_id" => $by->id, "limit" => $limit]
        );
        foreach ($arr as $pm) {
            $pms[] = PM::from_row($pm);
        }
        return $pms;
    }

    /**
     * @return PM[]|null
     */
    #[Field(extends: "User", name: "private_messages", type: "[PrivateMessage!]")]
    public static function get_pms_to_and_by(User $to, User $from, int $limit=null): ?array
    {
        global $database;

        $pms = [];
        $arr = $database->get_all(
            "SELECT * FROM private_message WHERE from_id = :from_id AND to_id = :to_id AND archived_by IS DISTINCT FROM :from_id AND archived_by IS DISTINCT FROM -1 ORDER BY sent_date DESC LIMIT :limit",
            ["from_id" => $from->id,"to_id" => $to->id, "limit" => $limit]
        );
        foreach ($arr as $pm) {
            $pms[] = PM::from_row($pm);
        }
        return $pms;
    }

    /**
     * @return PM[]|null
     */
    #[Field(extends: "User", name: "private_messages", type: "[PrivateMessage!]")]
    public static function get_pm_archive(User $of, int $limit=null): ?array
    {
        global $database;

        $pms = [];
        $arr = $database->get_all(
            "SELECT * FROM private_message WHERE archived_by = :of_id OR ((from_id = :of_id OR to_id = :of_id) AND archived_by = -1 ) ORDER BY sent_date DESC LIMIT :limit",
            ["of_id" => $of->id, "limit" => $limit]
        );
        foreach ($arr as $pm) {
            $pms[] = PM::from_row($pm);
        }
        return $pms;
    }

    #[Field(extends: "User", name: "private_message_unread_count")]
    public static function count_unread_pms(User $duser): ?int
    {
        global $database, $user;

        if (!$user->can(Permissions::READ_PM)) {
            return null;
        }
        if (($duser->id != $user->id) && !$user->can(Permissions::VIEW_OTHER_PMS)) {
            return null;
        }

        return (int)$database->get_one(
            "SELECT COUNT(*) FROM private_message WHERE to_id = :to_id AND is_read = :is_read AND archived_by IS DISTINCT FROM :to_id AND archived_by IS DISTINCT FROM -1",
            ["is_read" => false, "to_id" => $duser->id]
        );
    }

    #[Mutation(name: "create_private_message")]
    public static function send_pm(int $to_user_id, string $subject, string $message): bool
    {
        global $user;
        if (!$user->can(Permissions::SEND_PM)) {
            return false;
        }
        send_event(new SendPMEvent(new PM($user->id, get_real_ip(), $to_user_id, $subject, $message)));
        return true;
    }
}

class PrivMsg extends Extension
{
    /** @var PrivMsgTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        
        // shortcut to latest
        if ($this->get_version("pm_version") < 1) {
            $database->create_table("private_message", "
				id SCORE_AIPK,
				from_id INTEGER NOT NULL,
				from_ip SCORE_INET NOT NULL,
				to_id INTEGER NOT NULL,
				sent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				subject VARCHAR(192) NOT NULL,
				message TEXT NOT NULL,
				is_read BOOLEAN NOT NULL DEFAULT FALSE,
                archived_by INTEGER,
				FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
			");
            $database->execute("CREATE INDEX private_message__to_id ON private_message(to_id)");
            $this->set_version("pm_version", 5);
        }

        if ($this->get_version("pm_version") < 2) {
            log_info("pm", "Adding foreign keys to private messages");
            $database->execute("delete from private_message where to_id not in (select id from users);");
            $database->execute("delete from private_message where from_id not in (select id from users);");
            $database->execute("ALTER TABLE private_message
			ADD FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
			ADD FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;");
            $this->set_version("pm_version", 2);
        }

        if ($this->get_version("pm_version") < 3) {
            $database->standardise_boolean("private_message", "is_read", true);
            $this->set_version("pm_version", 3);
        }
        if ($this->get_version("pm_version") < 4) {
            $database->execute("ALTER TABLE private_message ALTER COLUMN subject TYPE VARCHAR(192);"); // 64 got very annoying with how long RE: threads
            $this->set_version("pm_version", 4);
        }
        if ($this->get_version("pm_version") < 5) {
            $database->execute("ALTER TABLE private_message
			add column archived_by INTEGER;");
            $this->set_version("pm_version", 5);
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::READ_PM)) {
            $count = $this->count_pms($user);
            if ($count > 0){
                $event->add_nav_link("pm", new Link('user#private-messages'), "Messages ($count)",null,11);
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "user") {
            if ($user->can(Permissions::READ_PM)) {
                $count = $this->count_pms($user);
                $h_count = $count > 0 ? SPAN(["class" => 'unread'], "($count)") : "";
                $event->add_nav_link("pm", new Link("/pm/list/{$user->id}"), emptyHTML("Private Messages", $h_count));
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::READ_PM)) {
            $count = $this->count_pms($user);
            $h_count = $count > 0 ? SPAN(["class" => 'unread'], "($count)") : "";
            $event->add_link(emptyHTML("Private Messages", $h_count), make_link("user", null, "private-messages"));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $page, $user;
        $duser = $event->display_user;
        if (!$user->is_anonymous() && !$duser->is_anonymous()) {
            if ($user->can(Permissions::READ_PM)) {
                if (($duser->id == $user->id) || $user->can(Permissions::VIEW_OTHER_PMS)){
                    $pms = PM::get_pms_to($duser, 5,);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms,from:true,more:$duser->id, archived:$duser->id);
                    }
                    $sent_pms = PM::get_pms_by($duser, 5);
                    if (!empty($sent_pms)){
                        $this->theme->display_pms($page, $sent_pms, header:"Sent messages", to:true, edit:true, delete:true, more:$duser->id, archived:$duser->id);
                    }
                } else{
                    $pms = PM::get_pms_to_and_by($duser, $user, 5);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, header:"Messages from you", to:true, edit:true, delete:true,more:$duser->id);
                    }
                }
            }
            if ($user->can(Permissions::SEND_PM) && $user->id != $duser->id) {
                $this->theme->display_composer($page, $user, $duser);
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $database, $page, $user;
        if ($event->page_matches("pm/read/{pm_id}", permission: Permissions::READ_PM)) {
            $pm_id = $event->get_iarg('pm_id');
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif (($pm["to_id"] == $user->id) || ($pm["from_id"] == $user->id) || $user->can(Permissions::VIEW_OTHER_PMS)) {
                $from_user = User::by_id((int)$pm["from_id"]);
                if ($pm["to_id"] == $user->id) {
                    $database->execute("UPDATE private_message SET is_read=true WHERE id = :id", ["id" => $pm_id]);
                    $cache->delete("pm-count-{$user->id}");
                }
                $pmo = PM::from_row($pm);
                $this->theme->display_message($page, $from_user, $user, $pmo);
                if ($user->can(Permissions::SEND_PM)) {
                    if ($pm["from_id"] == $user->id) $this->theme->display_edit_button($page,$pmo->id);
                    else $this->theme->display_composer($page, $user, $from_user, "Re: ".$pmo->subject);
                }
            } else {
                throw new PermissionDenied("You do not have permission to view this PM");
            }
        } 
        elseif ($event->page_matches("pm/list", method: "GET", permission: Permissions::READ_PM, paged:true)) {
            $duser_id = $event->get_iarg('page_num', 0);
            if (!$user->is_anonymous()) {
                if ($duser_id == 0 || ($duser_id == $user->id)) {
                    $pms = PM::get_pms_to($user);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, from:true, archived:$user->id);
                    }
                    $sent_pms = PM::get_pms_by($user);
                    if (!empty($sent_pms)){
                        $this->theme->display_pms($page, $sent_pms, header:"Sent messages", to:true, edit:true, delete:true, archived:$user->id);
                    } elseif (empty($pms)) {
                        throw new ObjectNotFound("You have no messages to display!");
                    }
                } elseif ($user->can(Permissions::VIEW_OTHER_PMS)) {
                    $duser = User::by_id($duser_id);
                    $pms = PM::get_pms_to($duser);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, from:true, archived:$duser->id);
                    }
                    $sent_pms = PM::get_pms_by($duser);
                    if (!empty($sent_pms)){
                        $this->theme->display_pms($page, $sent_pms, header:"Sent messages", to:true, edit:true, delete:true, archived:$duser->id);
                    } elseif (empty($pms)) {
                        throw new ObjectNotFound("You have no messages to display!");
                    }
                } else {
                    $duser = User::by_id($duser_id);
                    $pms = PM::get_pms_to_and_by($duser, $user);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, header:"Messages from you", to:true, edit:true, delete:true);
                    } else {
                        throw new ObjectNotFound("You have not sent anything to this user");
                    }
                }
            } else {
                throw new PermissionDenied("You are not allowed to see others' archives");
            }
        }
        elseif ($event->page_matches("pm/archived", method: "GET", permission: Permissions::READ_PM, paged:true)) {
            $duser_id = $event->get_iarg('page_num', 0);
            if (!$user->is_anonymous()) {
                if ($duser_id == 0 || ($duser_id == $user->id)) {
                    $pms = PM::get_pm_archive($user);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, header:"Archive", from:true, to:true, edit:true, archive:false, delete:true);
                    } else {
                        throw new ObjectNotFound("Your archive is empty!");
                    }
                } elseif ($user->can(Permissions::VIEW_OTHER_PMS)) {
                    $duser = User::by_id($duser_id);
                    $pms = PM::get_pm_archive($duser);
                    if (!empty($pms)) {
                        $this->theme->display_pms($page, $pms, header:"Archive from {$duser->name}", from:true, to:true, edit:true, archive:false, delete:true);
                    } else {
                        throw new ObjectNotFound("{$duser->name}'s archive is empty!");
                    }
                } else {
                    throw new PermissionDenied("You are not allowed to see others' archives");
                }
            } else {
                throw new PermissionDenied("You are not allowed to see others' archives");
            }
        }
        elseif ($event->page_matches("pm/archive", method: "POST", permission: Permissions::READ_PM)) {
            $pm_id = int_escape($event->req_POST("pm_id"));
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif (($pm["to_id"] == $user->id) || ($pm["from_id"] == $user->id)) {
                if (is_null($pm["archived_by"])){
                    $database->execute("UPDATE private_message SET archived_by = :u_id WHERE id = :id;", ["u_id" => $user->id, "id" => $pm_id]);
                } elseif ($pm["archived_by"] != $user->id){
                    $database->execute("UPDATE private_message SET archived_by = -1 WHERE id = :id;", ["id" => $pm_id]);
                } else {
                    throw new PermissionDenied("This PM is already archived for you");
                }
                if (($pm["to_id"] == $user->id)){
                    $cache->delete("pm-count-{$user->id}");
                } else $cache->delete("pm-count-".$pm["from_id"]);
                log_info("pm", "Archived PM #$pm_id", "PM archived");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            }
        }
        elseif ($event->page_matches("pm/delete", method: "POST", permission: Permissions::READ_PM)) {
            $pm_id = int_escape($event->req_POST("pm_id"));
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif (($pm["to_id"] == $user->id) || ($pm["from_id"] == $user->id) || $user->can(Permissions::VIEW_OTHER_PMS)) {
                $database->execute("DELETE FROM private_message WHERE id = :id", ["id" => $pm_id]);
                if (($pm["to_id"] == $user->id)){
                    $cache->delete("pm-count-{$user->id}");
                } else $cache->delete("pm-count-".$pm["from_id"]);
                log_info("pm", "Deleted PM #$pm_id", "PM deleted");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            }
        }
        elseif ($event->page_matches("pm/send", method: "POST", permission: Permissions::SEND_PM)) {
            $to_id = int_escape($event->req_POST("to_id"));
            $from_id = $user->id;
            $subject = $event->req_POST("subject");
            $message = $event->req_POST("message");
            /** @var SendPMEvent $PMe */
            $PMe = send_event(new SendPMEvent(new PM($from_id, get_real_ip(), $to_id, $subject, $message)));
            
            $page->set_mode(PageMode::REDIRECT);
            $page->flash("PM sent");
            if ($PMe->id) $page->set_redirect(make_link("pm/read/{$PMe->id}"));
            else $page->set_redirect(referer_or(make_link()));
        }
        elseif ($event->page_matches("pm/edit/{pm_id}", permission: Permissions::SEND_PM)) {
            $pm_id = $event->get_iarg('pm_id');
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif ($pm["from_id"] == $user->id) {
                $pmo = PM::from_row($pm);
                $subject = $pmo->subject;
                if (substr($subject, -9) === " (edited)") {
                    $subject = substr($subject, 0, -9);
                }
                $this->theme->display_editor($page, $pmo->id, $subject, $pmo->message, $pmo->to_id);
            } else {
                throw new PermissionDenied("You do not have permission to edit this PM");
            }
        }
        elseif ($event->page_matches("pm/edit", method: "POST", permission: Permissions::SEND_PM)) {
            $pm_id = int_escape($event->req_POST("pm_id"));
            $pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", ["id" => $pm_id]);
            if (is_null($pm)) {
                throw new ObjectNotFound("No such PM");
            } elseif ($pm["from_id"] == $user->id) {
                $pmo = PM::from_row($pm);
                $pmo->subject = $event->req_POST("subject");
                $pmo->message = $event->req_POST("message");
                $pmo->from_ip = get_real_ip();
                send_event(new EditPMEvent($pmo));
                $page->flash("PM edited");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("pm/read/$pm_id"));
            }
        }
    }

    public function onSendPM(SendPMEvent $event): void
    {
        global $cache, $database;
        $database->execute(
            "
				INSERT INTO private_message(
					from_id, from_ip, to_id,
					sent_date, subject, message)
				VALUES(:fromid, :fromip, :toid, now(), :subject, :message)",
            ["fromid" => $event->pm->from_id, "fromip" => $event->pm->from_ip,
            "toid" => $event->pm->to_id, "subject" => $event->pm->subject, "message" => $event->pm->message]
        );
        $event->id = $database->get_last_insert_id("private_message_id_seq");
        $cache->delete("pm-count-{$event->pm->to_id}");
        log_info("pm", "Sent PM to User #{$event->pm->to_id}");
    }

    public function onEditPM(EditPMEvent $event): void
    {
        global $cache, $database;
        $database->execute("
            UPDATE private_message SET 
            (from_ip,sent_date,subject,message,is_read) = (:fromip,now(),:subject,:message,false)
            WHERE id = :id;",
            ["fromip" => $event->pm->from_ip,"subject" => $event->pm->subject. " (edited)", "message" => $event->pm->message, "id" => $event->pm->id]
        );
        log_info("pm", "Edited PM #{$event->pm->id}");
    }

    private function count_pms(User $user): int
    {
        global $database;

        return cache_get_or_set(
            "pm-count-{$user->id}",
            fn () => $database->get_one("
                SELECT count(*)
                FROM private_message
                WHERE to_id = :to_id
                AND is_read = :is_read
                AND archived_by IS DISTINCT FROM :to_id
                AND archived_by IS DISTINCT FROM -1
            ", ["to_id" => $user->id, "is_read" => false]),
            600
        );
    }
}
