<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, INPUT, OPTION, SELECT, TABLE, TD, TEXTAREA, TH, TR, emptyHTML};

class UserThemesTheme extends Themelet
{
    /** @param string[] $themes */
    public function admin_actions(array $themes): void
    {
        $html = emptyHTML();
        if (!empty($themes)) {
            $options = SELECT(["name" => "name"]);
            foreach ($themes as $theme) {
                $options->appendChild(OPTION(["value" => $theme], $theme));
            }
            $html->appendChild(
                SHM_SIMPLE_FORM(
                    make_link("admin/user_theme_action"),
                    B("Delete/Edit"),
                    TABLE(
                        ["class" => "form"],
                        TR(
                            TD(["colspan" => 2], $options),
                        ),
                        TR(
                            TD(SHM_SUBMIT("Delete")),
                            TD(SHM_SUBMIT("Edit", ["name" => "edit"])),
                        ),
                    )
                )
            );
        }
        $html->appendChild(
            SHM_SIMPLE_FORM(
                make_link("admin/user_theme_add"),
                B("Create new"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TH("Name"),
                        TD(INPUT(["type" => "text", "name" => "name"]))
                    ),
                    TR(
                        TH("Style"),
                        TD(TEXTAREA(["name" => "style"]))
                    ),
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Create")),
                    ),
                )
            )
        );
        Ctx::$page->add_block(new Block("User themes", $html));
    }

    public function display_editor(string $name, string $current_contents): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("admin/user_theme_edit"),
            INPUT(["type" => "hidden", "name" => "name", "value" => $name]),
            TABLE(
                ["class" => "form"],
                TR(TD(TEXTAREA(
                    ["name" => "style", "rows" => 20],
                    $current_contents
                ))),
                TR(TD(SHM_SUBMIT("Edit")))
            ),
            "Please note that after editing you will need to do a full cache refresh to see the changes!"
        );
        Ctx::$page->add_block(new Block("Editing theme \"$name\"", $html));
    }
}
