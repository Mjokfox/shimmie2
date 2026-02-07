<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LINK;

/** @extends Extension<UserThemesTheme> */
class UserThemes extends Extension
{
    public const KEY = "user_themes";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $utheme = Ctx::$user->get_config()->get(UserThemesUserConfig::THEME);
        if (!is_null($utheme)) {
            $path = Filesystem::data_path("user_themes/$utheme.css", false);
            if ($path->exists()) {
                $data_href = (string)Url::base();
                Ctx::$page->add_html_header(LINK([
                    'rel' => 'stylesheet',
                    'href' => "$data_href/{$path->str()}",
                    'type' => 'text/css'
                ]), 100);
            }
        }

        if ($event->page_matches("user_themes/edit/{name}", permission: AdminPermission::MANAGE_ADMINTOOLS)) {
            $name = strtolower($event->get_arg("name"));
            $path = Filesystem::data_path("user_themes/$name.css", false);
            if (!$path->exists()) {
                Ctx::$page->flash("Theme \"$name\" doesn't exist!");
                Ctx::$page->set_redirect(Url::referer_or(make_link("admin")));
                return;
            }
            $style = $path->get_contents();
            $this->theme->display_editor($name, $style);
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $themes = self::get_user_themes();
        unset($themes["default"]);
        $this->theme->admin_actions($themes);
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "user_theme_add":
                $name = strtolower($event->params->req("name"));
                $style = $event->params->req("style");
                $path = Filesystem::data_path("user_themes/$name.css");
                $path->put_contents($style);
                Ctx::$page->flash("Added theme \"$name\"");
                break;
            case "user_theme_edit":
                $name = strtolower($event->params->req("name"));
                $style = $event->params->req("style");
                $path = Filesystem::data_path("user_themes/$name.css", false);
                if (!$path->exists()) {
                    Ctx::$page->flash("Theme \"$name\" doesn't exist!");
                    $event->redirect = false;
                    Ctx::$page->set_redirect(Url::referer_or(make_link("user_themes/edit/$name")));
                    break;
                }
                $path->put_contents($style);
                Ctx::$page->flash("Edited theme \"$name\"");
                break;
            case "user_theme_action":
                $name = $event->params->req("name");
                if ($event->params->offsetExists("edit")) {
                    $event->redirect = false;
                    Ctx::$page->set_redirect(make_link("user_themes/edit/$name"));
                    break;
                }
                $path = Filesystem::data_path("user_themes/$name.css", false);
                $path->unlink();
                Ctx::$page->flash("Deleted theme \"$name\"");
                break;
        }
    }

    /** @return string[] */
    public static function get_user_themes(): array
    {
        $out = ["default" => ""];
        $paths = Filesystem::get_dir_contents(Filesystem::data_path("user_themes"));
        foreach ($paths as $path) {
            $name = \basename($path->str(), ".css");
            $out[$name] = $name;
        }
        return $out;
    }
}
