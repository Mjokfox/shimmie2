<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

new UserClass("user", "base", [
    Permissions::BIG_SEARCH => true,
    Permissions::CREATE_IMAGE => true,
    Permissions::CREATE_COMMENT => true,
    Permissions::EDIT_IMAGE_TAG => true,
    Permissions::EDIT_IMAGE_SOURCE => true,
    Permissions::EDIT_IMAGE_TITLE => true,
    Permissions::EDIT_IMAGE_RELATIONSHIPS => true,
    Permissions::EDIT_IMAGE_ARTIST => true,
    Permissions::CREATE_IMAGE_REPORT => true,
    Permissions::EDIT_IMAGE_RATING => true,
    Permissions::EDIT_FAVOURITES => true,
    Permissions::CREATE_VOTE => true,
    Permissions::SEND_PM => true,
    Permissions::READ_PM => true,
    Permissions::SET_PRIVATE_IMAGE => true,
    Permissions::CHANGE_USER_SETTING => true,
    Permissions::FORUM_CREATE => true,
    Permissions::NOTES_CREATE => true,
    Permissions::NOTES_EDIT => true,
    Permissions::NOTES_REQUEST => true,
    Permissions::POOLS_CREATE => true,
    Permissions::POOLS_UPDATE => true,
]);

new UserClass("verified", "user",[    
    Permissions::PERFORM_BULK_ACTIONS => true,
    Permissions::BULK_DOWNLOAD => true,
]);

class EmailVerification extends Extension
{
    public function get_priority(): int 
    {
        return 75;
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        
        if ($event->page_matches("email_verification",method:"GET")) {
            $ruser = User::by_name($user->name); // need non cached user
            if ($ruser->class->name === "user"){
                $token = $_GET['token'];
                if ($token != null) {
                    if ($token === $ruser->get_auth_token()) {
                        $ruser->set_class("verified");
                        $page->flash("Email verified!");
                        $page->add_block(new Block("Email verified", rawHTML(""), "main", 1));
                    }
                    else {
                        throw new PermissionDenied("Permission Denied: Invalid Token (Are you opening the verification link in the same browser?)");
                    }
                } else {throw new PermissionDenied("Permission Denied: No token supplied");}
            } else {throw new PermissionDenied("Permission Denied: You are already verified!");}
        } 
        else if ($event->page_matches("user_admin/send_verification_mail",method:"POST")){
            $ruser = User::by_name($user->name);
            
            if ($event->req_POST('id') == $ruser->id) {
                if ($ruser->email){
                    $this->send_verification_mail($ruser->get_auth_token(), $ruser->email);
                } else {$page->flash("no email set, cannot send verification email");}
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            }
        } 
        else if ($event->page_matches("user_admin/change_email", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'address' => 'email',
            ]);
            $ruser = User::by_id($input['id']);
            if ($this->user_can_edit_user($user, $ruser)) {
                if ($ruser->class->name === "verified" || $ruser->class->name === "user") {
                    $ruser->set_class("user");
                    $this->send_verification_mail($ruser->get_auth_token(), $input['address']);
                }

            }
        }
    }

    public function onUserCreation(UserCreationEvent $event): void{
        global $page;
        $page->flash("Welcome to FindAfox, ". $event->username ."!");
        $this->send_verification_mail($event->get_user()->get_auth_token(), $event->email);
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void {
        global $page, $user;
        $ruser = User::by_name($user->name);
        $duser = $event->display_user;
        if ($ruser->class->name == "user" && $duser == $ruser) {
            if ($duser->email){
                $html = emptyHTML();
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    "user_admin/send_verification_mail",
                    "",
                    emptyHTML()
                    ,
                    "Resend verification email"
                ));
                $page->add_block(new Block("Verify", $html, "main", 61));
            }
        }
    }

    public function send_verification_mail(string $token, string $email): void
    {
        global $page;
        if ($email === "") {
            $page->flash("You are now registered as a basic user, having a maximum of 20 uploads per day, email verified users get 100 uploads per day and the ability to bulk download images. This is as an anti spam measure, no data will be sold to third parties.");
            return;
        }
        if ($token === "") {
            $page->flash("verification email failed to send, to verify please try again by clicking the button in the account panel to become verified user");
            return;
        }
        $verification_url = "https://findafox.net/email_verification?token=$token"; //yeah lets hardcode for now
        $to = $email;
        $subject = 'Verify your email';
        $message = "Your email verification link, please open it in the same browser as you need to be logged in<br><a href=$verification_url>$verification_url</a>";
        $headers = array(
            'Content-type' => 'text/html',
            'Sender' => 'mjokfox@findafox.net', //yeah lets hardcode for now
            'From' => 'Email verification findafox <mjokfox@findafox.net>',
            'Reply-To' => 'findafox staff <mjokfox@findafox.net>',
            'X-Mailer' => 'PHP/' . phpversion()
        );
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
            ($b->can(Permissions::PROTECTED) && $a->class->name == "admin") ||
            (!$b->can(Permissions::PROTECTED) && $a->can(Permissions::EDIT_USER_INFO))
        ) {
            return true;
        } else {
            throw new PermissionDenied("You need to be an admin to change other people's details");
        }
    }
}
