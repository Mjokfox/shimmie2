<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

/*
 * Sent when the setup screen's 'Save Settings' button has been activated
 */
final class ConfigSaveEvent extends Event
{
    /**
     * @param array<string, null|string|int|boolean|array<string>> $values
     */
    public function __construct(
        public Config $config,
        public array $values
    ) {
        parent::__construct();
    }

    /**
     * Convert POST data to settings data, eg
     *
     *     $_POST = [
     *         "_type_mynull" => "string",
     *         "_type_mystring" => "string",
     *         "_config_mystring" => "hello world!",
     *         "_type_myint" => "int",
     *         "_config_myint" => "42KB",
     *     ]
     *
     * becomes
     *
     *     $config = [
     *         "mynull" => null,
     *         "mystring" => "hello world!",
     *         "myint" => 43008,
     *     ]
     *
     * @param array<string, string|string[]> $post
     * @return array<string, null|string|int|boolean|array<string>>
     */
    public static function postToSettings(array $post): array
    {
        $settings = [];
        foreach ($post as $key => $type) {
            if (str_starts_with($key, "_type_")) {
                $key = str_replace("_type_", "", $key);
                $value = $post["_config_$key"] ?? null;
                if ($value === "") {
                    $value = null;
                }
                if ($type === "string") {
                    $settings[$key] = $value;
                } elseif ($type === "int") {
                    assert(!is_array($value));
                    $settings[$key] = $value ? parse_shorthand_int($value) : null;
                } elseif ($type === "bool") {
                    $settings[$key] = $value === "on";
                } elseif ($type === "array") {
                    $settings[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $value = implode(", ", $value);
                    }
                    throw new InvalidInput("Invalid type '$value' for key '$key'");
                }
            }
        }
        return $settings;
    }
}

final class Setup extends Extension
{
    public const KEY = "setup";
    /** @var SetupTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;

        if ($event->page_starts_with("nicedebug")) {
            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::JSON);
            $page->set_data(\Safe\json_encode([
                "args" => $event->args,
                "theme" => get_theme(),
                "nice_urls" => $config->get_bool(SetupConfig::NICE_URLS, false),
                "base" => (string)Url::base(),
                "absolute_base" => (string)Url::base()->asAbsolute(),
                "base_link" => (string)make_link(""),
                "search_example" => (string)search_link(["AC/DC"]),
            ]));
        }

        if ($event->page_matches("nicetest")) {
            $page->set_mode(PageMode::DATA);
            $page->set_data("ok");
        }

        if ($event->page_matches("setup", method: "GET", permission: SetupPermission::CHANGE_SETTING)) {
            $blocks = [];
            foreach (ConfigGroup::get_subclasses() as $class) {
                $group = $class->newInstance();
                if ($group::is_enabled()) {
                    $block = $this->theme->config_group_to_block($config, $group);
                    if ($block) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->theme->display_page($blocks);
        } elseif ($event->page_matches("setup/save", method: "POST", permission: SetupPermission::CHANGE_SETTING)) {
            send_event(new ConfigSaveEvent($config, ConfigSaveEvent::postToSettings($event->POST)));
            $page->flash("Config saved");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(Url::referer_or(make_link("setup")));
        }
    }

    public function onConfigSave(ConfigSaveEvent $event): void
    {
        $config = $event->config;
        foreach ($event->values as $key => $value) {
            match(true) {
                is_null($value) => $config->delete($key),
                is_string($value) => $config->set_string($key, $value),
                is_int($value) => $config->set_int($key, $value),
                is_bool($value) => $config->set_bool($key, $value),
                is_array($value) => $config->set_array($key, $value),
            };
        }
        Log::warning("setup", "Configuration updated");
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('config:defaults')
            ->setDescription('Show defaults')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                foreach (ConfigGroup::get_all_defaults() as $key => $value) {
                    $output->writeln("$key: " . var_export($value, true));
                }
                return Command::SUCCESS;
            });
        $event->app->register('config:get')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription('Get a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $config;
                $output->writeln($config->get_string($input->getArgument('key')));
                return Command::SUCCESS;
            });
        $event->app->register('config:set')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->setDescription('Set a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache, $config;
                $config->set_string($input->getArgument('key'), $input->getArgument('value'));
                $cache->delete("config");
                return Command::SUCCESS;
            });
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(SetupPermission::CHANGE_SETTING)) {
                $event->add_nav_link(make_link('setup'), "Board Config", order: 0);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(SetupPermission::CHANGE_SETTING)) {
            $event->add_link("Board Config", make_link("setup"));
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        global $config;
        $event->replace('$base', $config->get_string('base_href'));
        $event->replace('$title', $config->get_string(SetupConfig::TITLE));
    }
}
