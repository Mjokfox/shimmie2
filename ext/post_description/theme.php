<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TD, TEXTAREA, TR};

class PostDescriptionTheme extends Themelet
{
    public function get_description_editor_html(string $raw_description): HTMLElement
    {
        $tfe = send_event(new TextFormattingEvent($raw_description));

        return SHM_POST_INFO(
            "Description",
            $tfe->getFormattedHTML(),
            Ctx::$user->can(PostDescriptionPermission::EDIT_IMAGE_DESCRIPTIONS)
            ? TEXTAREA([
                "type" => "text",
                "name" => "description",
                "id" => "description_editor",
                "class" => "formattable",
                ], $raw_description)
            : null
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
                    "placeholder" => "Description",
                    "class" => "formattable",
                ])
            )
        );
    }
}
