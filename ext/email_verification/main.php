<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

class EmailVerification extends Extension
{
    public function get_priority(): int
    {
        return 51;
    }

    public function onInitExt(InitExtEvent $event): void
    {

        new UserClass("user", "base", [
            SpeedHaxPermission::BIG_SEARCH => true,
            ImagePermission::CREATE_IMAGE => true,
            CommentPermission::CREATE_COMMENT => true,
            PostTagsPermission::EDIT_IMAGE_TAG => true,
            PostSourcePermission::EDIT_IMAGE_SOURCE => true,
            PostTitlesPermission::EDIT_IMAGE_TITLE => true,
            RelationshipsPermission::EDIT_IMAGE_RELATIONSHIPS => true,
            ArtistsPermission::EDIT_IMAGE_ARTIST => true,
            ReportImagePermission::CREATE_IMAGE_REPORT => true,
            RatingsPermission::EDIT_IMAGE_RATING => true,
            FavouritesPermission::EDIT_FAVOURITES => true,
            NumericScorePermission::CREATE_VOTE => true,
            PrivMsgPermission::SEND_PM => true,
            PrivMsgPermission::READ_PM => true,
            PrivateImagePermission::SET_PRIVATE_IMAGE => true,
            UserAccountsPermission::CHANGE_USER_SETTING => true,
            ForumPermission::FORUM_CREATE => true,
            NotesPermission::CREATE => true,
            NotesPermission::EDIT => true,
            NotesPermission::REQUEST => true,
            PoolsPermission::CREATE => true,
            PoolsPermission::UPDATE => true,
        ]);

        new UserClass("verified", "user", [
            BulkActionsPermission::PERFORM_BULK_ACTIONS => true,
            BulkDownloadPermission::BULK_DOWNLOAD => true,
        ]);

    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("email_verification", method:"GET")) {
            $user = User::by_id($user->id); // cached user can give problems
            if ($user->class->name === "user" && !is_null($user->email)) {
                $token = $_GET['token'];
                if ($token != null) {
                    if ($token === $this->get_email_token($user, $user->email)) {
                        $user->set_class("verified");
                        $page->flash("Email verified!");
                        $page->add_block(new Block("Email verified", rawHTML(""), "main", 1));
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
            $user = User::by_id($user->id);
            if ($event->req_POST('id') == $user->id) {
                if ($user->email) {
                    $this->send_verification_mail($this->get_email_token($user, $user->email), $user->email);
                } else {
                    $page->flash("no email set, cannot send verification email");
                }
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            }
        } elseif ($event->page_matches("user_admin/change_email", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'address' => 'email',
            ]);
            $duser = User::by_id($input['id']);
            if ($this->user_can_edit_user($user, $duser)) {
                if ($duser->class->name === "verified" || $duser->class->name === "user") {
                    $duser->set_class("user");
                    $this->send_verification_mail($this->get_email_token($duser, $input['address']), $input['address']);
                }
            }
        }
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        global $page, $config;
        $title = $config->get_string(SetupConfig::TITLE);
        $page->flash("Welcome to $title, {$event->username}!");
        $this->send_verification_mail($this->get_email_token($event->get_user(), $event->email), $event->email);
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $page, $user;
        $ruser = User::by_name($user->name);
        $duser = $event->display_user;
        if ($ruser->class->name == "user" && $duser == $ruser) {
            if ($duser->email) {
                $html = emptyHTML();
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    "user_admin/send_verification_mail",
                    "",
                    emptyHTML(),
                    "Resend verification email"
                ));
                $page->add_block(new Block("Verify", $html, "main", 61));
            }
        }
    }

    public function send_verification_mail(string $token, string $email): void
    {
        global $page, $config;
        if ($email === "") {
            $page->flash($config->get_string(EmailVerificationConfig::DEFAULT_MESSAGE, ""));
            return;
        }
        if ($token === "") {
            $page->flash("verification email failed to send, to verify please try again by clicking the button in the account panel to become verified user");
            return;
        }
        $sender = $config->get_string(EmailVerificationConfig::EMAIL_SENDER);
        if (is_null($sender)) {
            $page->flash("Email verification not setup by site owner");
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
            $page->flash("Email verification mail sent, please check your inbox and spam");
        } else {
            $page->flash("Email verification mail failed to send, please retry later to become verified user");
        }
    }

    // very shamelessly stolen from user/main.php, why is it private there?! lets make it public here :3
    public function user_can_edit_user(User $a, User $b): bool
    {
        if ($a->is_anonymous()) {
            throw new PermissionDenied("You aren't logged in");
        }

        if (
            ($a->name == $b->name) ||
            ($b->can(UserAccountsPermission::PROTECTED) && $a->class->name == "admin") ||
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
