<?php

declare(strict_types=1);

namespace Shimmie2;

class EmailVerificationConfig extends ConfigGroup
{
    public const KEY = "email_verification";
    #[ConfigMeta("Sender email", ConfigType::STRING)]
    public const EMAIL_SENDER = "email_verification_sender";

    #[ConfigMeta("Message for users who did not set an email", ConfigType::STRING, default: "", input: ConfigInput::TEXTAREA)]
    public const DEFAULT_MESSAGE = "email_verification_def_mess";
}
