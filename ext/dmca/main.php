<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B,TABLE,TR,TD,INPUT, rawHTML};

class Dmca extends Extension
{
    /** @var DmcaTheme */
    protected Themelet $theme;
    public function get_priority(): int
    {
        return 1;
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page;
        if ($event->page_matches("dmca", method: "GET")) {
            $this->theme->display_page();
        } elseif ($event->page_matches("dmca_submit", method: "POST")) {
            if ($this->send_mail()) {
                $this->theme->display_submitted();
            } else {
                $this->theme->display_failed();
            }
        }
    }
    public function send_mail(): bool
    {
        global $page, $config;
        if (!isset($_POST['dmca_email']) and
            !isset($_POST['dmca_input'])) {
            return false;
        }
        $to = $config->get_string(DmcaConfig::EMAIL);
        if (empty($to)) {
            $page->flash("The administrator of this site has not set an email to send to, please try to contact them in some way.");
            return false;
        }
        $subject = 'DMCA takedown request';
        $message = $_POST['dmca_input'];
        $headers = 'From: ' . $_POST['dmca_email'] . "\r\n" .
                'Reply-To: ' . $_POST['dmca_email'] . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

        if (mail($to, $subject, $message, $headers)) {
            return true;
        } else {
            return false;
        }
    }
}
