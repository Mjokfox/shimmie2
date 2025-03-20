<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML, rawHTML,INPUT, A, BR};

class CustomPostOwnerTheme extends PostOwnerTheme
{
    public function get_owner_editor_html(Image $image): HTMLElement
    {
        global $user;
        $owner = $image->get_owner()->name;
        $date = SHM_DATE($image->posted);
        $ip = $user->can(IPBanPermission::VIEW_IP) ? rawHTML(" (" . SHM_IP($image->owner_ip, "Post posted {$image->posted}") . ")") : "";
        /** @var BuildAvatarEvent $avatar_e */
        $avatar_e = send_event(new BuildAvatarEvent($image->get_owner()));
        $avatar = $avatar_e->html;
        return SHM_POST_INFO(
            "Uploader",
            emptyHTML(A(["class" => "username", "href" => make_link("user/$owner")], $owner), $ip, ", ", $date, BR(), $avatar),
            $user->can(PostOwnerPermission::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "owner", "value" => $owner]) : null
        );
    }
}
