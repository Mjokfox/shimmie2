<?php

declare(strict_types=1);

namespace Shimmie2;

class CronUploader extends Extension
{
    /** @var CronUploaderTheme */
    protected Themelet $theme;

    public const NAME = "cron_uploader";

    // TODO: Checkbox option to only allow localhost + a list of additional IP addresses that can be set in /cron_upload

    public const QUEUE_DIR = "queue";
    public const UPLOADED_DIR = "uploaded";
    public const FAILED_DIR = "failed_to_upload";

    private static bool $IMPORT_RUNNING = false;

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "system") {
            $event->add_nav_link("cron_docs", new Link('cron_upload'), "Cron Upload");
        }
    }

    /**
     * Checks if the cron upload page has been accessed
     * and initializes the upload.
     */
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("cron_upload/run")) {
            $this->process_upload();
        } elseif ($event->page_matches("cron_upload", permission: CronUploaderPermission::CRON_RUN)) {
            $this->display_documentation();
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $failed_dir = $this->get_failed_dir();
        $results = get_dir_contents($failed_dir);

        $failed_dirs = [];
        foreach ($results as $result) {
            $path = join_path($failed_dir, $result);
            if (is_dir($path)) {
                $failed_dirs[] = $result;
            }
        }

        $this->theme->display_form($failed_dirs);
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        $action = $event->action;
        switch ($action) {
            case "cron_uploader_clear_queue":
                $event->redirect = true;
                $this->clear_folder(self::QUEUE_DIR);
                break;
            case "cron_uploader_clear_uploaded":
                $event->redirect = true;
                $this->clear_folder(self::UPLOADED_DIR);
                break;
            case "cron_uploader_clear_failed":
                $event->redirect = true;
                $this->clear_folder(self::FAILED_DIR);
                break;
            case "cron_uploader_restage":
                $event->redirect = true;
                if (array_key_exists("failed_dir", $event->params) && !empty($event->params["failed_dir"])) {
                    $this->restage_folder($event->params["failed_dir"]);
                }
                break;
        }
    }

    public function onLog(LogEvent $event): void
    {
        global $user;

        if (self::$IMPORT_RUNNING) {
            $all = $user->get_config()->get_bool(CronUploaderUserConfig::INCLUDE_ALL_LOGS);
            if ($event->priority >= $user->get_config()->get_int(CronUploaderUserConfig::LOG_LEVEL) &&
                ($event->section == self::NAME || $all)) {
                $output = "[" . date('Y-m-d H:i:s') . "] " . ($all ? '[' . $event->section . '] ' : '') . "[" . LogLevel::from($event->priority)->name . "] " . $event->message;

                echo $output . "\r\n";
                flush_output();

                $log_path = $this->get_log_file();
                file_put_contents($log_path, $output);
            }
        }
    }

    private function restage_folder(string $folder): void
    {
        global $page;
        if (empty($folder)) {
            throw new InvalidInput("folder empty");
        }
        $queue_dir = $this->get_queue_dir();
        $stage_dir = join_path($this->get_failed_dir(), $folder);

        if (!is_dir($stage_dir)) {
            throw new InvalidInput("Could not find $stage_dir");
        }

        $this->prep_root_dir();

        $results = get_files_recursively($stage_dir);

        if (count($results) == 0) {
            if (remove_empty_dirs($stage_dir) === false) {
                $page->flash("Nothing to stage from $folder, cannot remove folder");
            } else {
                $page->flash("Nothing to stage from $folder, removing folder");
            }
            return;
        }
        foreach ($results as $result) {
            $new_path = join_path($queue_dir, substr($result, strlen($stage_dir)));

            if (file_exists($new_path)) {
                $page->flash("File already exists in queue folder: " .$result);
                return;
            }
        }

        $success = true;
        foreach ($results as $result) {
            $new_path = join_path($queue_dir, substr($result, strlen($stage_dir)));

            $dir = dirname($new_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if (rename($result, $new_path) === false) {
                $page->flash("Could not move file: " .$result);
                $success = false;
            }
        }

        if ($success === true) {
            $page->flash("Re-staged $folder to queue");
            if (remove_empty_dirs($stage_dir) === false) {
                $page->flash("Could not remove $folder");
            }
        }
    }

    private function clear_folder(string $folder): void
    {
        global $page, $user;
        $path = join_path($this->get_user_dir(), $folder);
        deltree($path);
        $page->flash("Cleared $path");
    }


    private function get_cron_url(): string
    {
        global $user;

        $user_api_key = $user->get_config()->get_string(UserConfigUserConfig::API_KEY, "API_KEY");

        return make_http(make_link("/cron_upload/run", "api_key=".urlencode($user_api_key)));
    }

    private function get_cron_cmd(): string
    {
        return "curl --silent " . $this->get_cron_url();
    }

    private function display_documentation(): void
    {
        global $database;

        $this->prep_root_dir();

        $queue_dir = $this->get_queue_dir();
        $uploaded_dir = $this->get_uploaded_dir();
        $failed_dir = $this->get_failed_dir();

        $queue_dirinfo = scan_dir($queue_dir);
        $uploaded_dirinfo = scan_dir($uploaded_dir);
        $failed_dirinfo = scan_dir($failed_dir);


        $running = false;
        $lockfile = \Safe\fopen($this->get_lock_file(), "w");
        try {
            if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
                $running = true;
            } else {
                flock($lockfile, LOCK_UN);
            }
        } finally {
            fclose($lockfile);
        }

        $logs = [];
        if (Extension::is_enabled(LogDatabaseInfo::KEY)) {
            $logs = $database->get_all(
                "SELECT * FROM score_log WHERE section = :section ORDER BY date_sent DESC LIMIT 100",
                ["section" => self::NAME]
            );
        }

        $this->theme->display_documentation(
            $running,
            $queue_dirinfo,
            $uploaded_dirinfo,
            $failed_dirinfo,
            $this->get_cron_cmd(),
            $this->get_cron_url(),
            $logs
        );
    }

    private function get_user_dir(): string
    {
        global $user;
        return $user->get_config()->get_string(
            CronUploaderUserConfig::DIR,
            data_path(join_path("cron_uploader", $user->name))
        );
    }

    public function get_queue_dir(): string
    {
        return join_path($this->get_user_dir(), self::QUEUE_DIR);
    }

    public function get_uploaded_dir(): string
    {
        return join_path($this->get_user_dir(), self::UPLOADED_DIR);
    }

    public function get_failed_dir(): string
    {
        return join_path($this->get_user_dir(), self::FAILED_DIR);
    }

    private function prep_root_dir(): string
    {
        // Make the directory if it doesn't exist yet
        if (!is_dir($this->get_queue_dir())) {
            mkdir($this->get_queue_dir(), 0775, true);
        }
        if (!is_dir($this->get_uploaded_dir())) {
            mkdir($this->get_uploaded_dir(), 0775, true);
        }
        if (!is_dir($this->get_failed_dir())) {
            mkdir($this->get_failed_dir(), 0775, true);
        }

        return $this->get_user_dir();
    }

    private function get_lock_file(): string
    {
        return join_path($this->get_user_dir(), ".lock");
    }

    /**
     * Uploads the image & handles everything
     */
    public function process_upload(): bool
    {
        global $database, $user, $config, $page, $_shm_load_start;

        $max_time = intval(ini_get('max_execution_time')) * .8;

        $page->set_mode(PageMode::MANUAL);
        $page->set_mime(MimeType::TEXT);
        $page->send_headers();

        if (!$config->get_bool(UserAccountsConfig::ENABLE_API_KEYS)) {
            throw new ServerError("User API keys are not enabled. Please enable them for the cron upload functionality to work.");
        }

        if ($user->is_anonymous()) {
            throw new UserError("User not present. Please specify the api_key for the user to run cron upload as.");
        }

        log_info(self::NAME, "Logged in as user {$user->name}");

        if (!$user->can(CronUploaderPermission::CRON_RUN)) {
            throw new PermissionDenied("User does not have permission to run cron upload");
        }

        $lockfile = \Safe\fopen($this->get_lock_file(), "w");
        if (!flock($lockfile, LOCK_EX | LOCK_NB)) {
            throw new ServerError("Cron upload process is already running");
        }

        self::$IMPORT_RUNNING = true;
        try {
            //shm_set_timeout(null);

            $output_subdir = date('Ymd-His', time());
            $image_queue = $this->generate_image_queue();

            // Randomize Images
            //shuffle($this->image_queue);

            $merged = 0;
            $added = 0;
            $failed = 0;

            // Upload the file(s)
            foreach ($image_queue as $img) {
                $execution_time = ftime() - $_shm_load_start;
                if ($execution_time > $max_time) {
                    break;
                } else {
                    $remaining = $max_time - $execution_time;
                    log_debug(self::NAME, "Max run time remaining: $remaining");
                }
                try {
                    $result = $database->with_savepoint(function () use ($img, $output_subdir) {
                        log_info(self::NAME, "Adding file: {$img[0]} - tags: {$img[2]}");
                        $result = $this->add_image($img[0], $img[1], $img[2]);
                        $this->move_uploaded($img[0], $img[1], $output_subdir, false);
                        return $result;
                    });
                    if ($result->merged) {
                        $merged++;
                    } else {
                        $added++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    log_error(self::NAME, "(" . gettype($e) . ") " . $e->getMessage());
                    log_error(self::NAME, $e->getTraceAsString());
                    if ($user->get_config()->get_bool(CronUploaderUserConfig::STOP_ON_ERROR)) {
                        break;
                    } else {
                        $this->move_uploaded($img[0], $img[1], $output_subdir, true);
                    }
                }
            }

            // Throw exception if there's nothing in the queue
            if ($merged + $failed + $added === 0) {
                log_warning(self::NAME, "Your queue is empty so nothing could be uploaded.");
                return false;
            }

            log_info(self::NAME, "Items added: $added");
            log_info(self::NAME, "Items merged: $merged");
            log_info(self::NAME, "Items failed: $failed");


            return true;
        } finally {
            self::$IMPORT_RUNNING = false;
            flock($lockfile, LOCK_UN);
            fclose($lockfile);
        }
    }

    private function move_uploaded(string $path, string $filename, string $output_subdir, bool $corrupt = false): void
    {
        global $user;

        $rootDir = $this->get_user_dir();
        $rootLength = strlen($rootDir);
        if ($rootDir[$rootLength - 1] == "/" || $rootDir[$rootLength - 1] == "\\") {
            $rootLength--;
        }

        $relativeDir = dirname(substr($path, $rootLength + 7));

        if ($relativeDir == ".") {
            $relativeDir = "";
        }

        // Determine which dir to move to
        if ($corrupt) {
            // Move to corrupt dir
            $newDir = join_path($this->get_failed_dir(), $output_subdir, $relativeDir);
            $info = "ERROR: Post was not uploaded. ";
        } else {
            $newDir = join_path($this->get_uploaded_dir(), $output_subdir, $relativeDir);
            $info = "Post successfully uploaded. ";
        }

        if (!is_dir($newDir)) {
            mkdir($newDir, 0775, true);
        }

        $newFile = join_path($newDir, $filename);
        // move file to correct dir
        rename($path, $newFile);

        log_info(self::NAME, $info . "Post \"$filename\" moved from queue to \"$newDir\".");
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     *
     * @param string[] $tags
     */
    private function add_image(string $tmpname, string $filename, array $tags): DataUploadEvent
    {
        $event = send_event(new DataUploadEvent($tmpname, basename($filename), 0, [
            'tags' => Tag::implode($tags),
        ]));

        // Generate info message
        if (count($event->images) == 0) {
            throw new UploadException("File type not recognised (".$event->mime."). Filename: {$filename}");
        } elseif ($event->merged === true) {
            $infomsg = "Post merged. ID: {$event->images[0]->id} - Filename: {$filename}";
        } else {
            $infomsg = "Post uploaded. ID: {$event->images[0]->id} - Filename: {$filename}";
        }
        log_info(self::NAME, $infomsg);

        return $event;
    }

    private const PARTIAL_DOWNLOAD_EXTENSIONS = ['crdownload','part'];
    private const SKIPPABLE_FILES = ['.ds_store', 'thumbs.db', 'desktop.ini', '.listing'];

    private function is_skippable_file(string $path): bool
    {
        $info = pathinfo($path);

        if (in_array(strtolower($info['basename']), self::SKIPPABLE_FILES)) {
            return true;
        }

        if (array_key_exists("extension", $info) && in_array(strtolower($info['extension']), self::PARTIAL_DOWNLOAD_EXTENSIONS)) {
            return true;
        }

        return false;
    }

    private function generate_image_queue(): \Generator
    {
        $base = $this->get_queue_dir();

        if (!is_dir($base)) {
            log_warning(self::NAME, "Post Queue Directory could not be found at \"$base\".");
            return;
        }

        $ite = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($ite) as $fullpath => $cur) {
            if (!is_link($fullpath) && !is_dir($fullpath) && !$this->is_skippable_file($fullpath)) {
                $relativePath = substr($fullpath, strlen($base));
                $tags = path_to_tags($relativePath);

                yield [
                    0 => $fullpath,
                    1 => pathinfo($fullpath, PATHINFO_BASENAME),
                    2 => $tags
                ];
            }
        }
    }

    private function get_log_file(): string
    {
        return join_path($this->get_user_dir(), "uploads.log");
    }
}
