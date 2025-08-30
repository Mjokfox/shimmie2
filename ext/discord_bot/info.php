<?php

declare(strict_types=1);

namespace Shimmie2;

final class DiscordBotInfo extends ExtensionInfo
{
    public const KEY = "discord_bot";

    public string $key = self::KEY;
    public string $name = "Discord bot integration";
    public string $url = "https://findafox.net";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Sends logs over udp in a format for a discord bot";
}
