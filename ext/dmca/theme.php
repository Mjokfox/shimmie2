<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{H2, BR, DIV, INPUT, TEXTAREA};

class DmcaTheme extends Themelet
{
    public function display_page(): void
    {
        global $page;

        $page->set_title("DMCA form");
        $html = SHM_SIMPLE_FORM(
            "dmca_submit",
            DIV(
                ["style" => "width:50%"],
                "Your contact email",
                INPUT(["type" => 'text',"required" => "true", "name" => 'dmca_email', "placeholder" => "email@example.com", "style" => "width:100%"]),
                BR(),
                BR(),
                "Reason(s) and offending image(s)",
                TEXTAREA(["type" => 'number',"required" => "true", "name" => 'dmca_input', "rows" => "8", "style" => "width:100%", "placeholder" => "ID(s) or URL(s) for offending item or items to be removed. Include details of who owns the items and your proof that you hold the copyright to request this takedown"]),
                BR(),
                SHM_SUBMIT('submit'),
            )
        );
        $page->add_block(new Block("DMCA takedown request form", $html, "main", 20));
    }

    public function display_submitted(): void
    {
        global $page;

        $page->set_title("DMCA request submitted");
        $html = H2("Your request has been sent, staff will get back to you as soon as possible");
        $page->add_block(new Block("DMCA request submitted", $html, "main", 20));
    }

    public function display_failed(): void
    {
        global $page;

        $page->set_title("DMCA request failed to submit");
        $html = H2("DMCA request failed to send, please check if all fields are filled in");
        $page->add_block(new Block("DMCA request failed to submit", $html, "main", 20));
    }
}
