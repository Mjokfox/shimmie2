<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML, INPUT};

require_once "config.php";

class ReverseImage extends Extension
{
    /** @var ReverseImageTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version(ReverseImageConfig::VERSION) < 1) {
            $database->execute("CREATE EXTENSION IF NOT EXISTS vector;");
            $database->create_table(
                'image_features',
                'image_id INTEGER,
                features vector(512),
                FOREIGN KEY(image_id) REFERENCES images(id) ON DELETE CASCADE,
                PRIMARY KEY(image_id)'
            );

            $this->set_version(ReverseImageConfig::VERSION, 1);

            log_info("Reverse_image", "extension installed");
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link("reverse image search", new Link('reverse_image_search'), "Reverse Image Search", order:51);
        }
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page, $config, $user;
        if ($event->page_matches("post/list", paged: true)
            || $event->page_matches("post/list/{search}", paged: true)) {
            if ($config->get_bool(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get_bool(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
                $this->theme->list_search($page);
            }
        } elseif ($event->page_matches("post/view/{id}")) {
            if ($config->get_bool(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get_bool(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
                $this->theme->view_search($page, $event->get_GET('search') ?? "");
            }
        } elseif ($event->page_matches("post/search", paged: true)
            || $event->page_matches("post/search/{search}", paged: true)
        ) {
            global $database;
            $get_search = $event->get_GET('search');
            if ($get_search || !($config->get_bool(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get_bool(ReverseImageUserConfig::USER_SEARCH_ENABLE))) {
                $page->set_mode(PageMode::REDIRECT);
                if (empty($get_search)) {
                    $page->set_redirect(make_link("/post/list"));
                } else {
                    $page->set_redirect(make_link("post/search/$get_search/1"));
                }
                return;
            }
            $search = $event->get_arg('search', "");
            if (empty($search)) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("/post/list"));
                return;
            }

            $feat = $this->get_search_features($search);
            if (!$feat) {
                $page->set_mode(PageMode::REDIRECT);
                $page->flash("something went wrong");
                $page->set_redirect(make_link("/post/list"));
                return;
            }
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = $config->get_int("index_images");

            $image_ids = $this->reverse_image_compare($feat, $page_size, ($page_number - 1) * $page_size);
            $in = implode(",", array_keys($image_ids));

            $res = $database->get_all(
                "SELECT images.* FROM images
                WHERE id IN ($in)
                order by array_position(array[$in], id);"
            );
            $images = [];
            foreach ($res as $r) {
                $images[] = new Image($r);
            }

            $plbe = send_event(new PostListBuildingEvent([]));
            $this->theme->list_search($page, $search);

            $image_count = $database->get_one("SELECT count(id) from images;");


            /** @var IndexTheme $IT */
            $IT = Themelet::get_for_extension_class("Index");
            $IT->set_page($page_number, (int)ceil($image_count / $page_size), [$search]);
            $IT->display_page($page, $images);

            if (count($plbe->parts) > 0) {
                $IT->display_admin_block($plbe->parts);
            }
        } elseif ($event->page_matches("reverse_image_search_fromupload", method: "POST", authed: false)) {
            $ids = $this->reverse_image_search_post();
            $page->set_mode(PageMode::DATA);
            if (count($ids) > 0) {
                $threshold = $config->get_int(ReverseImageConfig::SIMILARITY_DUPLICATE) / 100;
                $first = array_key_first($ids);
                $image = Image::by_id((int)$first);
                if (!is_null($image)) {
                    $closest = [
                        "id" => $first,
                        "link" => $image->get_thumb_link(),
                        "width" => $image->width,
                        "height" => $image->height,
                        "filesize" => $image->filesize,
                        "auto_dupe" => $ids[$first] < $threshold
                    ];
                } else {
                    $closest = null;
                }
                $tag_n = $this->tags_from_features_id($ids);
                $json_input = ["tags" => $tag_n,"closest" => $closest];
                $page->set_data(json_encode($json_input));
                $page->set_filename('tag_occurrences.json', 'Content-Type: application/json');
            } else {
                $page->set_data(json_encode(["No similar images found, either the file was not uploaded properly or no url given"]));
                $page->set_filename('failed.json', 'Content-Type: application/json');
            }
        } elseif ($event->page_matches("upload", method: "GET", permission: ImagePermission::CREATE_IMAGE)) {
            global $config, $user;
            $user_config = $user->get_config();
            $default_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
            $enable_auto_pre = $user_config->get_bool(ReverseImageUserConfig::USER_ENABLE_AUTO);
            $enable_auto_tag = $user_config->get_bool(ReverseImageUserConfig::USER_ENABLE_AUTO_SELECT);
            $predict_threshold = $user_config->get_int(ReverseImageUserConfig::USER_TAG_THRESHOLD);
            $html = "";
            if ($enable_auto_tag) {
                $r = 127 * (1 - ($predict_threshold / 100));
                $g = 255 * ($predict_threshold / 100);
                $man = $enable_auto_pre ? "Automatically" : "Semi manually";
                $html .= "<div>$man selecting tags with higher than <div, style='background-color:rgba($r,$g,0,0.5)'> $predict_threshold% [predicted] probability</div></div>";
            } elseif ($enable_auto_pre) {
                $html .= "<div>Note: automatic tag predicting is currently active, this can be disabled in user options. You can also enable automatic selecting in user options.</div>";
            }
            $enable_auto_pre = $enable_auto_pre ? "true" : "false";
            $enable_auto_tag = $enable_auto_tag ? "true" : "false";
            $page->add_block(new Block(null, rawHTML("$html<script>
            const DEFAULT_RIS_N = $default_reverse_result_limit;
            const ENABLE_AUTO_PREDICT = $enable_auto_pre;
            const ENABLE_AUTO_TAG = $enable_auto_tag; 
            const AUTO_TAG_THRESHOLD = $predict_threshold; 
            </script>"), "main", 100));
        } elseif ($event->page_matches("reverse_image_search", method: "GET")) {
            $this->theme->display_page();
        } elseif ($event->page_matches("reverse_image_search", method: "POST", authed: false)) {
            $ids = $this->reverse_image_search_post();
            if (count($ids) > 0) {
                $this->theme->display_page($_POST["reverse_image_limit"] ?? null);
                $this->theme->display_results($ids);
            } else {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect("reverse_image_search");
                $page->flash("Something broke in the backed or no file or url given");
            }
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin();
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;
        $event->add_part(
            SHM_SIMPLE_FORM(
                "reverse_image_search/",
                INPUT(["type" => "hidden", "name" => "hash", "value" => $event->image->hash]),
                INPUT([
                    "type" => "submit",
                    "value" => "Similar posts on this site",
                ])
            ),
            50
        );
    }


    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch ($event->action) {
            case "reverse_image":
                $start_time = ftime();
                $query = "SELECT a.id, a.hash
                FROM images a
                LEFT JOIN image_features b
                    ON a.id = b.image_id
                WHERE b.image_id IS NULL
                AND a.image = TRUE
                AND a.id > :id
                LIMIT :limit;";
                $images = $database->get_all($query, ["id" => $event->params['reverse_image_start_id'] | "0","limit" => $event->params['reverse_image_limit'] | "0"]);
                $i = 0;
                $j = [];
                foreach ($images as $image) {
                    $features = $this->get_image_features_by_hash($image["hash"]);
                    if (!$features) {
                        $j[] = $image["id"];
                    } else {
                        $this->add_features_to_db($features, $image["id"]);
                        $i++;
                    }
                }
                $ids = implode(",", $j);
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Added image features to the database for $i images in $exec_time seconds" . (count($j) > 0 ? ", but failed for image ids [$ids]" : ".");
                log_info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    /**
     * @param int[] $ids
     * @return array<string, mixed>
     */
    public function tags_from_features_id(array $ids): array
    {
        global $database;
        $ids_array = implode(",", array_keys($ids));

        $sum_case = "SUM(CASE\n";
        $i = 1;
        foreach (array_keys($ids) as $id) {
            $sum_case .= "WHEN b.image_id = $id THEN " . (1 - $ids[$id]) / $i++ . "\n";
        }
        $sum_case .= "ELSE 0\nEND) AS n\n";
        $query = "SELECT a.tag,
            $sum_case
            FROM tags a
            INNER JOIN image_tags b ON a.id = b.tag_id
            WHERE b.image_id IN ($ids_array)
            GROUP BY a.tag
            ORDER BY n DESC";

        return $database->get_pairs($query, []);
    }

    // adds features belonging to id to database
    /**
     * @param float[] $features
     */
    public function add_features_to_db(array $features, int $id): void
    {
        global $database;
        $feature_array = "[" . implode(",", $features) . "]";
        $query = "INSERT INTO image_features VALUES(:id,:feature_array)";
        $args = ["id" => $id,"feature_array" => $feature_array];
        $database->execute($query, $args);
    }

    // downloads an image from a given url, returns the full image path
    /**
     * @param non-empty-string $url
     */
    private function transload(string $url): string
    {
        $tmp_filename = shm_tempnam("transload");
        try {
            fetch_url($url, $tmp_filename);
        } catch (FetchException $e) {
            throw new UploadException("Error reading from $url: $e");
        }
        return $tmp_filename;
    }

    // helper function for the default post request
    /**
     * @return array<string, mixed>
     */
    public function reverse_image_search_post(): array
    {
        global $page, $config;
        if (isset($_POST["url"]) && $_POST["url"]) {
            $file = $this->transload($_POST["url"]);
        } elseif (isset($_POST["hash"]) && $_POST["hash"]) {
            $file = warehouse_path(Image::IMAGE_DIR, $_POST["hash"], false);
        } elseif (isset($_FILES['file'])) {
            if ($_FILES['file']['error']) {
                throw new UploadException("Upload failed: ".$_FILES['file']['error']);
            } else {
                $file = $_FILES['file']['tmp_name'];
            }
        } else {
            return [];
        }

        $features = $this->get_image_features($file);

        if (isset($_POST["url"]) && $_POST["url"]) {
            unlink($file);
        }

        if (!$features) {
            return [];
        }
        $limit = isset($_POST["reverse_image_limit"]) ? $_POST["reverse_image_limit"] : $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
        if ($limit > $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT)) {
            $limit = $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT);
        }
        return $this->reverse_image_compare($features, $limit);
    }

    // helper function
    /**
     * @return array<float>|false
     */
    public function get_image_features_by_hash(string $hash): array|false
    {
        return $this->get_image_features($_SERVER['DOCUMENT_ROOT'] ."/" . warehouse_path(Image::IMAGE_DIR, $hash));
    }

    // makes the post request to the engine.py, returns the features as array[512] or false if it failed
    /**
     * @return array<float>|false
     */
    public function get_image_features(string $path): array|false
    {
        global $config;
        $uri = $config->get_string(ReverseImageConfig::CONF_URL);
        $url = "$uri/extract_features";
        $ch = curl_init($url);
        assert($ch !== false);
        if (function_exists('curl_file_create')) { // php 5.5+
            $cFile = curl_file_create($path);
        } else { //
            $cFile = '@' . realpath($path);
        }
        $post = ['image' => $cFile];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /** @var false|string $result */
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            return false;
        }
        $json = json_decode($result, true);

        if (!isset($json["features"])) {
            return false;
        }
        return $json["features"];

    }

    /**
     * @return array<float>|false
     */
    public function get_search_features(string $search): array|false
    {
        global $config;
        $uri = $config->get_string(ReverseImageConfig::CONF_URL);
        $url = "$uri/search_features";
        $ch = curl_init($url);
        assert($ch !== false);
        $post = ['search' => $search];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /** @var false|string $result */
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            return false;
        }
        $json = json_decode($result, true);

        if (!isset($json["features"])) {
            return false;
        }
        return $json["features"];
    }

    // gets the closest image ids from the input features, returning an array[$limit] of these ids
    /**
     * @param float[] $features
     * @return array<string, mixed>
     */
    private function reverse_image_compare(array $features, int|string $limit, int $offset = null): array
    {
        global $database;
        $feature_array = "[" . implode(",", $features) . "]";
        $query = "SELECT image_id, features <=> :feature_array AS similarity
            FROM image_features
            ORDER BY similarity ASC
            LIMIT :limit";
        if ($offset) {
            $query .= "\nOFFSET $offset";
        }
        $args = ["feature_array" => $feature_array, "limit" => $limit];
        $image_ids = $database->get_pairs($query, $args);
        return $image_ids;
    }
}
