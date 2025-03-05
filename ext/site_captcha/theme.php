<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BODY, emptyHTML, TITLE, META, H1, SCRIPT, NOSCRIPT, IMG, DIV};

class SiteCaptchaTheme extends Themelet
{
    public function display_page(Page $page): void
    {
        $page->set_mode(PageMode::DATA);
        $page->add_http_header("Refresh: 3");
        $data_href = get_base_href();

        $page->set_data((string)$page->html_html(
            emptyHTML(
                TITLE("captcha verification"),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                SCRIPT(["type" => "text/javascript", "src" => "{$data_href}/ext/site_captcha/captcha.js"])
            ),
            BODY(
                ["style" => "background-color:#888;background-image:url(\"/captcha/css\");"],
                IMG(["id" => "img", "style" => "display:none;", "src" => "/captcha/image"]),
                H1("Automatically verifying you are not a bot, please wait..."),
                NOSCRIPT(
                    H1("Javascript disabled: Page will reload in 3 seconds.."),
                )
            )
        ));
    }

    public function display_block(Page $page): void
    {
        $page->add_block(new Block(
            null,
            DIV(
                ["style" => "background-image:url(\"/captcha/css\");"],
                IMG(["style" => "display:none;", "src" => "/captcha/image"])
            ),
            'main',
            id:"captcha"
        ));
    }
}
