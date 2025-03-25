<?php

declare(strict_types=1);

namespace Shimmie2;

final class DiscordBotConfig extends ConfigGroup
{
    public const KEY = "discord_bot";

    #[ConfigMeta("host:port", ConfigType::STRING, default: "127.0.0.1:10003")]
    public const HOST = "discord_bot_host";

}
