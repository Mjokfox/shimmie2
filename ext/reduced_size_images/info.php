<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReducedSizeImagesInfo extends ExtensionInfo
{
    public const KEY = "reduced_size_images";

    public string $name = "Full size image permission";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = 'Only users with permission can view full size images, resize images on upload automatically, requires change in reverse proxy config!';
    public ?string $documentation = "To get it working, you need to setup your reverse proxy for it. <br>
    Basically, the regular images are required to go through an authentication method, the extension listens on path \"_?images\" and returns status 200 or 403 depending if the user has the permission or not.<br>
    The smaller images are routed to \"_reduced_images/hash\" by default <br>
    Then how you set this up is up to you, a sample reverse proxy configuration for nginx:<br>
    <code><br>
            location /auth-images {<br>
            &emsp;include fastcgi_params;<br>
            &emsp;fastcgi_param SCRIPT_FILENAME \$document_root/index.php;<br>
            &emsp;fastcgi_pass unix:/run/php-fpm/php-fpm.sock;<br>
            &emsp;add_header Cache-Control \"no-store\";<br>
        }<br>
        <br>
        location ~ \"^/_?reduced_images/([0-9a-f]{2})([0-9a-f]{30})(.*)$\" {<br>
            &emsp;alias /var/www/shimmie2/data/reduced_images/$1/$1$2;<br>
            &emsp;try_files \"\" /fallback_images/$1$2$3;<br>
            &emsp;expires 30d;<br>
        }<br>
        <br>
        location ~ \"^/fallback_images/([0-9a-f]{2})([0-9a-f]{30}).*$\" {<br>
            &emsp;internal;<br>
            &emsp;alias /var/www/shimmie2/data/images/$1/$1$2;<br>
            &emsp;expires 30d;<br>
        }<br>
        <br>
        location ~ \"^/_?images/([0-9a-f]{2})([0-9a-f]{30}).*$\" {<br>
            &emsp;auth_request /auth-images;<br>
            &emsp;alias /var/www/shimmie2/data/images/$1/$1$2;<br>
            &emsp;expires 30d;<br>
        }<br>
    </code>";
}
