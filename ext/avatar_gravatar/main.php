<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\IMG;

class AvatarGravatar extends AvatarExtension
{
    public function get_priority(): int
    {
        return 50;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(AvatarGravatarConfig::GRAVATAR_SIZE, 80);
        $config->set_default_string(AvatarGravatarConfig::GRAVATAR_DEFAULT, "");
        $config->set_default_string(AvatarGravatarConfig::GRAVATAR_RATING, "g");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        global $config;
        $sb = $event->panel->create_new_block("Gravatar Avatar");
        $sb->start_table();
        $sb->add_choice_option(
            AvatarGravatarConfig::GRAVATAR_TYPE,
            [
                'Default' => 'default',
                'Wavatar' => 'wavatar',
                'Monster ID' => 'monsterid',
                'Identicon' => 'identicon'
            ],
            "Type",
            true
        );
        $sb->add_choice_option(
            AvatarGravatarConfig::GRAVATAR_RATING,
            ['G' => 'g', 'PG' => 'pg', 'R' => 'r', 'X' => 'x'],
            "Rating",
            true
        );

        $sb->end_table();
    }

    public function avatar_html(User $user): HTMLElement|null
    {
        global $config;

        if (!empty($user->email)) {
            $hash = md5(strtolower($user->email));
            $s = $config->get_string("avatar_gravatar_size");
            $d = urlencode($config->get_string("avatar_gravatar_default"));
            $r = $config->get_string("avatar_gravatar_rating");
            $cb = date("Y-m-d");
            $url = "https://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r&cacheBreak=$cb";
            return IMG(["alt" => "avatar", "class" => "avatar gravatar", "src" => $url]);
        }
        return null;
    }
}
