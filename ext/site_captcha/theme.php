<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BODY, emptyHTML, TITLE, META, H1, SCRIPT, NOSCRIPT, IMG, DIV};

class SiteCaptchaTheme extends Themelet
{
    public function display_page(): void
    {
        global $page;
        $page->set_mode(PageMode::DATA);
        $page->add_http_header("Refresh: 3");
        $data_href = Url::base();
        $time = time(); // add a 'random' string behind the image urls to avoid caching

        $page->set_data((string)$page->html_html(
            emptyHTML(
                TITLE("captcha verification"),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                SCRIPT(["type" => "text/javascript", "src" => "{$data_href}/ext/site_captcha/captcha.js"])
            ),
            BODY(
                ["style" => "background-color:#888;background-image:url(\"/captcha/css?$time\");"],
                IMG(["id" => "img", "style" => "display:none;", "src" => "/captcha/image?$time"]),
                H1("Automatically verifying you are not a bot, please wait..."),
                NOSCRIPT(
                    H1("Javascript disabled: Page will reload in 3 seconds.."),
                )
            )
        ));
    }

    public function display_block(): void
    {
        global $page;
        $page->add_block(new Block(
            null,
            DIV(
                ["style" => "background-image:url(\"/captcha/css\");"],
                IMG(["style" => "display:none;", "src" => "/captcha/image"])
            ),
            'subheading',
            id:"captcha"
        ));
    }

    public function display_cookie_image(string $cookie_name, string $token): void
    {
        global $page;
        $page->add_cookie(
            $cookie_name,
            $token,
            time() + 60 * 60 * 24 * 30,
            '/'
        );
        $page->set_mode(PageMode::MANUAL);
        $page->set_mime("image/jpg");
        $page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        $page->send_headers();
        print "1";
    }
}
