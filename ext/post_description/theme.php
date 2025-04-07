<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TD, TEXTAREA, TR};

class PostDescriptionTheme extends Themelet
{
    public function get_description_editor_html(Image $image): HTMLElement
    {
        global $user;
        /** @var TextFormattingEvent $tfe */
        $tfe = send_event(new TextFormattingEvent($image->offsetGet("description") ?? ""));
        return SHM_POST_INFO(
            "Description",
            $tfe->getFormattedHTML(),
            $user->can(ImagePermission::CREATE_IMAGE) ? TEXTAREA(["type" => "text", "name" => "description"], $tfe->original) : null
        );
    }

    public function get_upload_specific_html(string $suffix): HTMLElement
    {
        return TR(
            TD(
                ["colspan" => "100%"],
                TEXTAREA([
                    "type" => "text",
                    "name" => "description{$suffix}",
                    "placeholder" => "Description (512 characters max)"
                ])
            )
        );
    }
}
