<?php

declare(strict_types=1);

namespace Shimmie2;

final class Downtime extends Extension
{
    public const KEY = "downtime";
    /** @var DowntimeTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 10;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;

        if ($config->get_bool(DowntimeConfig::DOWNTIME)) {
            if (!$user->can(DowntimePermission::IGNORE_DOWNTIME) && !$this->is_safe_page($event)) {
                $msg = $config->get_string(DowntimeConfig::MESSAGE);
                $this->theme->display_message($msg);
                if (!defined("UNITTEST")) {  // hax D:
                    header("HTTP/1.1 {$page->code} Downtime");
                    print($page->data);
                    exit;
                }
            }
            $this->theme->display_notification();
        }
    }

    private function is_safe_page(PageRequestEvent $event): bool
    {
        if ($event->page_matches("user_admin/login")) {
            return true;
        } else {
            return false;
        }
    }
}
