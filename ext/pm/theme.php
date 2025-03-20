<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML,A,B,INPUT,TABLE,THEAD,TH,TBODY,TR,TD,TEXTAREA,DIV,BUTTON,H2,HR,BR};

class PrivMsgTheme extends Themelet
{
    /**
     * @param PM[] $pms
     */
    public function display_pms(Page $page, array $pms, string $header = "Inbox", bool $to = false, bool $from = false, bool $edit = false, bool $archive = true, bool $delete = true, int $more = 0, int $archived = 0): void
    {
        global $user;

        $tbody = TBODY();
        foreach ($pms as $pm) {
            $tr = TR();

            $subject = trim($pm->subject) ?: "(No subject)";
            if (!$pm->is_read) {
                $subject = B($subject);
            }
            $tr->appendChild(
                TD($pm->is_read ? "Y" : "N"),
                TD(A(["href" => make_link("pm/read/".$pm->id)], $subject))
            );
            if ($from) {
                $f_user = User::by_id_dangerously_cached($pm->from_id);
                $tr->appendChild(TD(A(["href" => make_link("user/".url_escape($f_user->name))], $f_user->name)));
            }
            if ($to) {
                $t_user = User::by_id_dangerously_cached($pm->to_id);
                $tr->appendChild(TD(A(["href" => make_link("user/".url_escape($t_user->name))], $t_user->name)));
            }
            $tr->appendChild(TD(substr($pm->sent_date, 0, 16)));
            $actions = DIV(["style" => "display:flex;"]);
            if ($archive) {
                $actions->appendChild(
                    SHM_SIMPLE_FORM(
                        make_link("pm/archive"),
                        INPUT(["type" => "hidden", "name" => "pm_id", "value" => $pm->id]),
                        SHM_SUBMIT("Archive")
                    )
                );
            }
            if ($delete) {
                $actions->appendChild(
                    DIV(
                        ["class" => "pm-edit"],
                        SHM_SIMPLE_FORM(
                            make_link("pm/delete"),
                            INPUT(["type" => "hidden", "name" => "pm_id", "value" => $pm->id]),
                            INPUT(["id" => "del-{$pm->id}", "onclick" => "$('#del-{$pm->id}').hide();$('#con-{$pm->id}').show();", "type" => "button", "value" => "Delete"]),
                            SHM_SUBMIT("This will also delete it for the other user, confirm?", ["id" => "con-{$pm->id}", "style" => "display:none;"])
                        )
                    )
                );
            }
            if ($edit) {
                $actions->appendChild(
                    BUTTON(
                        ["class" => "pm-edit", "onclick" => "location.href=\"".make_link("pm/edit/".$pm->id)."\";"],
                        "Edit"
                    )
                );
            }
            $tr->appendChild(TD($actions));

            $tbody->appendChild($tr);
        }
        $html = emptyHTML(
            TABLE(
                ["id" => "pms", "class" => "zebra"],
                THEAD(TR(TH("R?"), TH("Subject"), $from ? TH("From") : null, $to ? TH("To") : null, TH("Date"), TH("Action"))),
                $tbody
            ),
            $more != 0 ? A(["href" => make_link("pm/list/$more")], "See all") : null,
            $archived != 0 ? A(["href" => make_link("pm/archived/$archived"), "style" => "margin-left:1em"], "Archived messages") : null,
        );
        $page->add_block(new Block($header, $html, "main", 40, "private-messages"));
    }

    public function display_composer(Page $page, User $from, User $to, string $subject = ""): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("pm/send"),
            INPUT(["type" => "hidden", "name" => "to_id", "value" => $to->id]),
            TABLE(
                ["class" => "form pm-text"],
                TR(
                    TH("Subject"),
                    TD(INPUT(["type" => "text", "name" => "subject", "value" => $subject]))
                ),
                TR(TD(["colspan" => 2], TEXTAREA(["name" => "message", "rows" => 6]))),
                TR(TD(["colspan" => 2], SHM_SUBMIT("Send")))
            ),
        );
        $page->add_block(new Block("Write a PM", $html, "main", 50));
    }

    public function display_editor(Page $page, int $pm_id, string $subject = "", string $message = "", int $to_id = null): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("pm/edit"),
            INPUT(["type" => "hidden", "name" => "to_id", "value" => $pm_id]),
            TABLE(
                ["class" => "form pm-text"],
                TR(
                    TH("Subject"),
                    TD(INPUT(["type" => "text", "name" => "subject", "value" => $subject]))
                ),
                TR(TD(["colspan" => 2], TEXTAREA(["name" => "message", "rows" => 6], $message))),
                TR(TD(["colspan" => 2], SHM_SUBMIT("Edit")))
            ),
        );
        $page->add_block(new Block("Editing PM" . ($to_id ? " to ".User::by_id($to_id)->name : ""), $html, "main", 50));
    }

    public function display_edit_button(Page $page, int $pm_id): void
    {
        $page->add_block(new Block("", A(["href" => make_link("pm/edit/$pm_id")], "Edit"), "main", 49));
    }

    public function display_message(Page $page, User $from, User $to, PM $pm): void
    {
        $page->set_title("Private Message");
        $page->set_heading($pm->subject);
        $this->display_navigation();
        $page->add_block(new Block("Message from {$from->name}:", emptyHTML(H2($pm->subject), HR(), BR(), format_text($pm->message)), "main", 10));
    }
}
