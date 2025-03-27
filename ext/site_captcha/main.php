<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptcha extends Extension
{
    public const KEY = "site_captcha";
    /** @var SiteCaptchaTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 10;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        $image_cookie = $page->get_cookie("captcha_image");
        $css_cookie = $page->get_cookie("captcha_css");
        $image_token = $this->get_token("img");
        $css_token = $this->get_token("css");

        if ($event->page_matches("captcha/image", method:"GET")) {
            $this->theme->display_cookie_image("captcha_image", $image_token);
        } elseif ($event->page_matches("captcha/css", method:"GET")) {
            $this->theme->display_cookie_image("captcha_css", $css_token);
        } elseif (!$event->page_matches("robots.txt") && ($image_cookie !== $image_token || $css_cookie !== $css_token)) {
            if (!$this->is_ip_whitelisted()) {
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

    public function is_ip_whitelisted(): bool
    {
        global $cache, $config;
        $ips = $cache->get("captcha_whitelist_ips");
        $networks = $cache->get("captcha_whitelist_networks");
        if (is_null($ips) || is_null($networks)) {
            $rows = explode(",", $config->get_string(SiteCaptchaConfig::ALLOWED_IPS, ""));

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

            $cache->set("captcha_whitelist_ips", $ips, 60);
            $cache->set("captcha_whitelist_networks", $networks, 60);
        }
        $ip = Network::get_real_ip();
        if (in_array($ip, $ips)) {
            return true;
        } else {
            foreach ($networks as $range) {
                if (Network::ip_in_range($ip, $range)) {
                    return true;
                }
            }
        }
        return false;
    }
}
