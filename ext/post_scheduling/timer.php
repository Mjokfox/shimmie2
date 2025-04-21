<?php

declare(strict_types=1);

namespace Shimmie2;

// make it so it can only run once on a system
$pid_file = "data/temp/shimmie_post_scheduler.pid";

if (file_exists($pid_file)) {
    return;
}

file_put_contents($pid_file, getmypid());

try {
    // Handle signals
    pcntl_async_signals(true);

    function cleanup(string $pid_file): void
    {
        @unlink($pid_file);
        return;
    }

    pcntl_signal(SIGTERM, function () use ($pid_file) {cleanup($pid_file);});
    pcntl_signal(SIGINT, function () use ($pid_file) {cleanup($pid_file);});

    // load default stuffs so shimmie can run
    require_once "vendor/autoload.php";

    sanitize_php();
    version_check("8.2");

    @include_once "data/config/shimmie.conf.php";
    @include_once "data/config/extensions.conf.php";

    _set_up_shimmie_environment();
    Ctx::setTracer(new \EventTracer());
    // Override TS to show that bootstrapping started in the past
    Ctx::$tracer->begin("Bootstrap", raw: ["ts" => $_SERVER["REQUEST_TIME_FLOAT"] * 1e6]);
    _load_ext_files();
    // Depends on core files
    $cache = Ctx::setCache(load_cache(SysConfig::getCacheDsn()));
    $database = Ctx::setDatabase(new Database(SysConfig::getDatabaseDsn()));
    // $config depends on _load_ext_files (to load config.php files and
    // calculate defaults) and $cache (to cache config values)
    $config = Ctx::setConfig(new DatabaseConfig($database));
    // theme files depend on $config (theme name is a config value)
    _load_theme_files();
    // $page depends on theme files (to load theme-specific Page class)
    $page = Ctx::setPage(Themelet::get_theme_class(Page::class) ?? new Page());
    // $event_bus depends on ext/*/main.php being loaded
    Ctx::setEventBus(new EventBus());
    Ctx::$tracer->end();

    send_event(new InitExtEvent());

    $ps = new PostScheduling();

    global $argc, $argv;
    if ($argc > 1) {
        $timer = (int)$argv[1];
    } else {
        $timer = $ps->get_scheduled_post();
    }

    if ($timer <= 0) {
        @unlink($pid_file);
        return;
    }

    while (true) {
        sleep($timer);
        $config = Ctx::setConfig(new DatabaseConfig($database));
        Ctx::$database->begin_transaction();
        $timer = $ps->get_scheduled_post();
        if (Ctx::$database->is_transaction_open()) {
            Ctx::$database->commit();
        }
        if ($timer <= 0) {
            @unlink($pid_file);
            return;
        }
    }

} catch (\Exception $e) {
    @unlink($pid_file);
    throw $e;
}
