<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BODY, DIV, FORM, H1, IMG, INPUT, META, SCRIPT, STYLE, TITLE, emptyHTML};

class SiteCaptchaTheme extends Themelet
{
    public function display_page(): void
    {
        $url = make_link("captcha/check");
        Ctx::$page->add_http_header("Refresh: 3; url=$url");
        $data_href = Url::base();
        $time = time(); // add a 'random' string behind the image urls to avoid caching
        Ctx::$page->add_auto_html_headers();
        Ctx::$page->set_data(MimeType::HTML, (string)Ctx::$page->html_html(
            emptyHTML(
                TITLE("captcha verification"),
                META(["http-equiv" => "refresh", "url" => $url]),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                SCRIPT(["type" => "text/javascript", "src" => "{$data_href}/ext/site_captcha/captcha.js"]),
                STYLE("
                    .delayed-text {
                        visibility: hidden;
                        animation: showText forwards;
                        animation-delay: 3s;
                    }
                    @keyframes showText { to { visibility: unset }}
                "),
                ...Ctx::$page->get_all_html_headers(),
            ),
            BODY(
                ["style" => "background-image:url(\"/captcha/css?$time\");"],
                IMG(["id" => "img", "style" => "display:none;", "src" => "/captcha/image?$time"]),
                H1(["class" => "delayed-text"], "Loading..."),
                A(["class" => "delayed-text", "style" => "animation-delay:5s", "href" => $url], "Your browser might not be redirecting automatically, please click this link"),
            )
        ));

    }

    public function display_bot(string $image_token, string $css_token): void
    {
        $ref = Url::referer_or(make_link(""), ["captcha/check"]);
        Ctx::$page->add_auto_html_headers();
        Ctx::$page->set_data(MimeType::HTML, (string)Ctx::$page->html_html(
            emptyHTML(
                TITLE("captcha failed"),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                ...Ctx::$page->get_all_html_headers(),
            ),
            BODY(
                H1("You may have cookies disabled, please click this button to enter the site!"),
                FORM(
                    ["action" => make_link("captcha/verify"), "method" => "POST"],
                    INPUT(["type" => "hidden", "name" => "ref", "value" => $ref]),
                    INPUT(["type" => "hidden", "name" => "image_token", "value" => $image_token]),
                    INPUT(["type" => "hidden", "name" => "css_token", "value" => $css_token]),
                    INPUT(["type" => "submit", "value" => " I want foxes!! ", "style" => "font-size:2em;padding:.5em;margin:.5em"])
                )
            )
        ));
    }

    public function display_block(): void
    {
        Ctx::$page->add_block(new Block(
            null,
            DIV(
                ["style" => "background-image:url(\"/captcha/css\");"],
                IMG(["style" => "display:none;", "src" => "/captcha/image"])
            ),
            'subheading',
            id:"captcha",
            is_content:false
        ));
    }

    public function display_cookie_image(string $cookie_name, string $token): void
    {
        Ctx::$page->add_cookie(
            $cookie_name,
            $token,
            time() + 60 * 60 * 24 * 30,
            '/'
        );
        Ctx::$page->set_mode(PageMode::MANUAL);
        Ctx::$page->add_http_header("Content-Type: image/jpeg");
        Ctx::$page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        Ctx::$page->send_headers();
        print "1";
    }
}
