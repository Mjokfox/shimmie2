<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<SiteCaptchaTheme> */
class SiteCaptcha extends Extension
{
    public const KEY = "site_captcha";

    public function get_priority(): int
    {
        return 10;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $image_cookie = Ctx::$page->get_cookie("captcha_image");
        $css_cookie = Ctx::$page->get_cookie("captcha_css");
        $image_token = $this->get_token("img");
        $css_token = $this->get_token("css");

        if ($event->page_matches("captcha/image", method:"GET")) {
            $this->theme->display_cookie_image("captcha_image", $image_token);
        } elseif ($event->page_matches("captcha/css", method:"GET")) {
            $this->theme->display_cookie_image("captcha_css", $css_token);
        } elseif ($event->page_matches("captcha/check", method:"GET")) {
            if ($image_cookie === $image_token && $css_cookie === $css_token) {
                Ctx::$page->set_redirect(Url::referer_or(make_link(""), ["captcha/check"]));
            } else {
                $this->theme->display_bot($image_token, $css_token);
            }
            $event->stop_processing = true;
        } elseif ($event->page_matches("captcha/verify", method:"POST", authed:false)) {
            $ref = $event->POST->req("ref");
            $i_tok = $event->POST->req("image_token");
            $c_tok = $event->POST->req("css_token");
            if ($i_tok === $image_token && $c_tok === $css_token) {
                Ctx::$page->add_cookie(
                    "captcha_image",
                    $image_token,
                    time() + 60 * 60 * 24 * 30,
                    '/'
                );
                Ctx::$page->add_cookie(
                    "captcha_css",
                    $css_token,
                    time() + 60 * 60 * 24 * 30,
                    '/'
                );
            }
            Ctx::$page->set_redirect(Url::parse($ref));
            $event->stop_processing = true;
        } elseif (!$event->page_matches("robots.txt") && ($image_cookie !== $image_token || $css_cookie !== $css_token)) {
            if (!($this->is_useragent_whitelisted() || $this->is_ip_whitelisted())) {
                $this->theme->display_page();
                $event->stop_processing = true;
            }
        } else {
            $this->theme->display_block();
        }

    }

    private function get_token(string $extra = ""): string
    {
        return hash("sha3-256", Network::get_session_ip() . $extra . SECRET);
    }

    public function is_useragent_whitelisted(): bool
    {
        $uas = cache_get_or_set("captcha_whitelist_uas", function () {
            $rows = explode(",", Ctx::$config->get(SiteCaptchaConfig::ALLOWED_USERAGENTS) ?: "");
            $rows = array_map('trim', $rows);
            return array_filter($rows, fn ($v) => !empty($v));
        }, 60);

        $user_agent = $_SERVER["HTTP_USER_AGENT"] ?? "No UA";
        foreach ($uas as $ua) {
            if (str_contains($user_agent, $ua)) {
                return true;
            }
        }
        return false;
    }

    public function is_ip_whitelisted(): bool
    {
        $ips = Ctx::$cache->get("captcha_whitelist_ips");
        $networks = Ctx::$cache->get("captcha_whitelist_networks");
        if (is_null($ips) || is_null($networks)) {
            $rows = explode(",", Ctx::$config->get(SiteCaptchaConfig::ALLOWED_IPS) ?: "");

            $ips = []; # "0.0.0.0" => 123;
            $networks = []; # "0.0.0.0/32" => 456;
            foreach ($rows as $ip) {
                $ip = trim($ip);
                if (str_contains($ip, '/')) {
                    $networks[] = $ip;
                } else {
                    $ips[] = $ip;
                }
            }

            Ctx::$cache->set("captcha_whitelist_ips", $ips, 60);
            Ctx::$cache->set("captcha_whitelist_networks", $networks, 60);
        }
        $ip = Network::get_real_ip();
        if (in_array((string)$ip, $ips)) {
            return true;
        }
        foreach ($networks as $range) {
            if (IPRange::parse($range)->contains($ip)) {
                return true;
            }
        }
        return false;
    }
}
