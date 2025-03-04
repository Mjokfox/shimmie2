<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteCaptcha extends Extension
{
    /** @var SiteCaptchaTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        $cookie = $page->get_cookie("captcha_verified");
        $token = $this->get_token();

        if ($event->page_matches("captcha/token")) {
            $page->set_mode(PageMode::DATA);
            $page->set_data($token);
        } elseif ($event->page_matches("captcha/noscript", method:"POST", authed:false)) {
            $form_token = $event->req_POST("token");
            $test = trim($event->req_POST("test"));
            if (\safe\preg_match('/^(fo\S|f\Sx|\Sox|fuchs|vos|zorro|rav|rev|kettu)$/i', $test) && $form_token == $token) {
                $page->add_cookie(
                    "captcha_verified",
                    $token,
                    time() + 60 * 60 * 24 * 30,
                    '/'
                );
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link("")));
            } else {
                throw new UserError("Incorrect, you are either a bot, or not sure what the answer is. Hint: its the main animal of this site");
            }
        } elseif (is_null($cookie) || $cookie !== $this->get_token()) {
            if (!$this->is_ip_whitelisted()){
                $this->theme->display_page($page, $token);
            }
        }
    }

    private function get_token(): string
    {
        global $config;
        return hash("sha3-256", get_session_ip($config) . SECRET);
    }

    public function is_ip_whitelisted(): bool {
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
        $ip = get_real_ip();
        if (in_array($ip, $ips)) {
            return true;
        } else {
            foreach ($networks as $range) {
                if (ip_in_range($ip, $range)) {
                    return true;
                }
            }
        }
        return false;
    }
}
