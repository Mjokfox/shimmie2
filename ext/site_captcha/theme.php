<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BODY, emptyHTML, TITLE, META, H1, SCRIPT, NOSCRIPT, FORM, INPUT, LABEL, BR};

class SiteCaptchaTheme extends Themelet
{
    public function display_page(Page $page, string $token): void
    {
        $page->set_mode(PageMode::DATA);
        $data_href = get_base_href();

        $page->set_data((string)$page->html_html(
            emptyHTML(
                TITLE("captcha verification"),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                SCRIPT(["type" => "text/javascript", "src" => "{$data_href}/ext/site_captcha/captcha.js"])
            ),
            BODY(
                ["style" => "background-color:#888;"],
                NOSCRIPT(
                    H1("Javascript disabled: Please verify manually that you are not a bot"),
                    FORM(
                        ["action" => make_link("captcha/noscript"), "method" => 'POST'],
                        INPUT(["type" => "hidden", "name" => "token", "value" => $token]),
                        LABEL("Please fill in the missing word in this sentence:"),
                        BR(),
                        LABEL("The quick brown ___ jumps over the lazy dog."),
                        BR(),
                        INPUT(["type" => "text", "name" => "test", "autofocus" => true]),
                        BR(),
                        INPUT(["type" => "submit", "value" => "submit"])
                    )
                )
            )
        ));
    }
}
