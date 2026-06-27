<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, P, TD, TEXTAREA, TR, emptyHTML};

use MicroHTML\HTMLElement;

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
                B("Description "),
                TEXTAREA([
                    "type" => "text",
                    "name" => "description{$suffix}",
                    "placeholder" => "Description",
                    "class" => "formattable",
                ])
            )
        );
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts having some string in their description"),
            SHM_COMMAND_EXAMPLE("description=words", "Returns posts who's description contains the string 'words', case insensitive."),
            SHM_COMMAND_EXAMPLE("description=any", "Returns posts with any description"),
            SHM_COMMAND_EXAMPLE("description=none", "Returns posts with no description"),
        );
    }
}
