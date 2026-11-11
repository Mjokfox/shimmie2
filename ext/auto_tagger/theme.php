<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, DIV, INPUT, P, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

class AutoTaggerTheme extends Themelet
{
    /**
     * Show a page of auto-tag definitions.
     *
     * Note: $can_manage = whether things like "add new alias" should be shown
     */
    public function display_auto_tagtable(HTMLElement $table, HTMLElement $paginator): void
    {
        $page = Ctx::$page;
        $page->set_title("Auto-Tag List");
        $this->display_navigation();

        $page->add_block(new Block("Auto-Tag", emptyHTML(
            $table,
            $paginator,
            P(A(
                ["href" => make_link("auto_tag/export/auto_tag.csv"), "download" => "auto_tag.csv"],
                "Download as CSV"
            ))
        )));

        if (Ctx::$user->can(AutoTaggerPermission::MANAGE_AUTO_TAG)) {
            $page->add_block(new Block("Bulk Upload", SHM_FORM(
                action: make_link("auto_tag/import"),
                multipart: true,
                children: [
                    INPUT(["type" => "file", "name" => "auto_tag_file"]),
                    SHM_SUBMIT("Upload List")
                ]
            )));
        }
    }

    /**
     * @param non-empty-string $tag
     * @param non-empty-string $additional_tags
     */
    public function display_confirmation(string $tag, string $additional_tags, int $count): void
    {
        $form = SHM_SIMPLE_FORM(
            make_link("auto_tag/add"),
            INPUT(["type" => "hidden", "name" => "c_tag", "value" => $tag]),
            INPUT(["type" => "hidden", "name" => "c_additional_tags", "value" => $additional_tags]),
            INPUT(["type" => "hidden", "name" => "confirm", "value" => "on"]),
            INPUT(["type" => "submit", "value" => "Confirm"]),
            A(["href" => make_link("alias/list"), "style" => "margin-left:1em;"], "No, go back"),
        );

        $html = DIV(
            SPAN("Adding tag(s) \"", B($additional_tags), "\" to existing tag \"", B($tag), "\"."),
            BR(),
            SPAN("This will affect ", A(["href" => search_link([$tag])], B($count), " posts"), ", are you sure?"),
            $form
        );

        Ctx::$page->add_block(new Block("Confirm auto tag", $html));
    }
}
