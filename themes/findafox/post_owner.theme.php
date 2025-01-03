<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML, rawHTML,INPUT, A};

class CustomPostOwnerTheme extends PostOwnerTheme
{
    public function get_owner_editor_html(Image $image): HTMLElement
    {
        global $user;
        $owner = $image->get_owner()->name;
        $date = rawHTML(autodate($image->posted));
        $ip = $user->can(Permissions::VIEW_IP) ? rawHTML(" (" . show_ip($image->owner_ip, "Post posted {$image->posted}") . ")") : "";
        return SHM_POST_INFO(
            "Uploader",
            emptyHTML(A(["class" => "username", "href" => make_link("user/$owner")], $owner), $ip, ", ", $date,rawHTML("<br>".$image->get_owner()->get_avatar_html())),
            $user->can(Permissions::EDIT_IMAGE_OWNER) ? INPUT(["type" => "text", "name" => "owner", "value" => $owner]) : null
            
        );
    }
}