<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, rawHTML};

class EmailVerification extends Extension
{
    public const KEY = "email_verification";
    public function get_priority(): int
    {
        return 71; // after perm_manager
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            Ctx::$database->execute(
                "INSERT INTO user_classes (name, parent, description) VALUES (:name, :parent, :description)",
                ["name" => "verified", "parent" => "user", "description" => "email verified users"]
            );
            $this->set_version(1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("email_verification", method:"GET")) {
            Ctx::$user = User::by_id(Ctx::$user->id); // cached user can give problems
            if (Ctx::$user->class->name === "user" && !is_null(Ctx::$user->email)) {
                $token = $_GET['token'];
                if (!is_null($token)) {
                    if ($token === $this->get_email_token(Ctx::$user, Ctx::$user->email)) {
                        Ctx::$user->set_class("verified");
                        Ctx::$page->flash("Email verified!");
                        Ctx::$page->add_block(new Block("Email verified", rawHTML(""), "main", 1));
                    } else {
                        throw new PermissionDenied("Verification failed: Invalid Token (Are you logged in?)");
                    }
                } else {
                    throw new PermissionDenied("Permission Denied: No token supplied");
                }
            } else {
                throw new PermissionDenied("Your email is already verified!");
            }
        } elseif ($event->page_matches("user_admin/send_verification_mail", method:"POST")) {
            Ctx::$user = User::by_id(Ctx::$user->id);
            if ((int)$event->POST->req('id') === Ctx::$user->id) {
                if (Ctx::$user->email) {
                    $this->send_verification_mail($this->get_email_token(Ctx::$user, Ctx::$user->email), Ctx::$user->email);
                } else {
                    Ctx::$page->flash("no email set, cannot send verification email");
                }
                Ctx::$page->set_redirect(make_link("user"));
            }
        } elseif ($event->page_matches("user_admin/change_email", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'address' => 'email',
            ]);
            $duser = User::by_id($input['id']);
            if ($this->user_can_edit_user(Ctx::$user, $duser)) {
                if ($duser->class->name === "verified" || $duser->class->name === "user") {
                    $duser->set_class("user");
                    $this->send_verification_mail($this->get_email_token($duser, $input['address']), $input['address']);
                }
            }
        }
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        $title = Ctx::$config->get(SetupConfig::TITLE);
        Ctx::$page->flash("Welcome to $title, {$event->username}!");
        $this->send_verification_mail($this->get_email_token($event->get_user(), $event->email), $event->email);
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $ruser = User::by_name(Ctx::$user->name);
        $duser = $event->display_user;
        if ($ruser->class->name === "user" && $duser === $ruser) {
            if ($duser->email) {
                $html = emptyHTML();
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/send_verification_mail"),
                    "",
                    emptyHTML(),
                    "Resend verification email"
                ));
                Ctx::$page->add_block(new Block("Verify", $html, "main", 61));
            }
        }
    }

    public function send_verification_mail(string $token, string $email): void
    {
        if ($email === "") {
            Ctx::$page->flash(Ctx::$config->get(EmailVerificationConfig::DEFAULT_MESSAGE));
            return;
        }
        if ($token === "") {
            Ctx::$page->flash("verification email failed to send, to verify please try again by clicking the button in the account panel to become verified user");
            return;
        }
        $sender = Ctx::$config->get(EmailVerificationConfig::EMAIL_SENDER);
        if (is_null($sender)) {
            Ctx::$page->flash("Email verification not setup by site owner");
            return;
        }
        $server_name = $_SERVER['SERVER_NAME'];
        $verification_url = "https://$server_name/email_verification?token=$token";
        $to = $email;
        $subject = 'Verify your email address';
        $message = "Your email verification link, you require to be logged in when you open the link<br><a href=$verification_url>$verification_url</a>";
        $headers = [
            'Content-type' => 'text/html',
            'Sender' => $sender,
            'From' => "Email verification $server_name <$sender>",
            'Reply-To' => "$server_name admins <$sender>",
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        if (mail($to, $subject, $message, $headers)) {
            Ctx::$page->flash("Email verification mail sent, please check your inbox and spam");
        } else {
            Ctx::$page->flash("Email verification mail failed to send, please retry later to become verified user");
        }
    }

    // very shamelessly stolen from user/main.php, why is it private there?! lets make it public here :3
    public function user_can_edit_user(User $a, User $b): bool
    {
        if ($a->is_anonymous()) {
            throw new PermissionDenied("You aren't logged in");
        }

        if (
            ($a->name === $b->name) ||
            ($b->can(UserAccountsPermission::PROTECTED) && $a->class->name === "admin") ||
            (!$b->can(UserAccountsPermission::PROTECTED) && $a->can(UserAccountsPermission::EDIT_USER_INFO))
        ) {
            return true;
        } else {
            throw new PermissionDenied("You need to be an admin to change other people's details");
        }
    }

    public function get_email_token(User $user, string $email): string
    {
        return hash("sha3-256", $email . $user->get_session_id() . SECRET);
    }

}
