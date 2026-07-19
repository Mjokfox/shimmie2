<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, INPUT, SCRIPT, SPAN, emptyHTML};

require_once "config.php";

/** @extends Extension<ReverseImageTheme> */
class ReverseImage extends Extension
{
    public const KEY = "reverse_image";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            Ctx::$database->execute("CREATE EXTENSION IF NOT EXISTS vector;");
            Ctx::$database->create_table(
                'image_features',
                'image_id INTEGER,
                features vector(512),
                FOREIGN KEY(image_id) REFERENCES images(id) ON DELETE CASCADE,
                PRIMARY KEY(image_id)'
            );

            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('reverse_image_search'), "Reverse Image Search", order:51);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        $user = Ctx::$user;
        $config = Ctx::$config;
        if ($event->page_matches("post/list", paged: true)
            || $event->page_matches("post/list/{search}", paged: true)) {
            if ($config->get(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
                $this->theme->list_search();
            }
        } elseif ($event->page_matches("post/view/{id}")) {
            if ($config->get(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
                $this->theme->view_search($event->GET->get('search') ?? "");
            }
        } elseif ($event->page_matches("post/search", paged: true)
            || $event->page_matches("post/search/{search}", paged: true)
        ) {
            $get_search = $event->GET->get('search');
            if ($get_search || !($config->get(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get(ReverseImageUserConfig::USER_SEARCH_ENABLE))) {
                if (empty($get_search)) {
                    $page->set_redirect(make_link("post/list"));
                } else {
                    $page->set_redirect(make_link("post/search/$get_search/1"));
                }
                return;
            }
            $search = $event->get_arg('search', "");
            if (empty($search)) {
                $page->set_redirect(make_link("post/list"));
                return;
            }

            $feat = $this->get_search_features($search);
            if (!$feat) {
                $page->flash("something went wrong");
                $page->set_redirect(make_link("post/list"));
                return;
            }
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = $config->get(IndexConfig::IMAGES);

            $image_ids = $this->reverse_image_compare($feat, $page_size, ($page_number - 1) * $page_size);
            /** @var IndexTheme $IT */
            $IT = Themelet::get_theme_class(IndexTheme::class);
            $images = [];
            if (!empty($image_ids)) {
                $in = implode(",", array_keys($image_ids));
                $query = "SELECT images.* FROM images
                    WHERE id IN ($in)
                    order by array_position(array[$in], id);";
                // @phpstan-ignore-next-line
                $res = Ctx::$database->get_all($query);
                foreach ($res as $r) {
                    $images[] = new Post($r);
                }
            }
            $this->theme->list_search($search);

            $image_count = Ctx::$database->get_one("SELECT count(id) from images;");

            send_event(new PostListBuildingEvent([$search]));
            $IT->set_page($page_number, (int)ceil($image_count / $page_size), [$search]);
            $IT->display_page($images);
        } elseif ($event->page_matches("upload", method: "GET", permission: ImagePermission::CREATE_IMAGE)) {
            $user_config = $user->get_config();
            $enable_predicting = $user_config->get(ReverseImageUserConfig::USER_ENABLE_PREDICTING);
            if ($enable_predicting) {
                $enable_tagging = $user_config->get(ReverseImageUserConfig::USER_ENABLE_TAGGING);
                $predict_threshold = $user_config->get(ReverseImageUserConfig::USER_TAG_THRESHOLD);
                $html = emptyHTML();
                if ($enable_tagging) {
                    $r = 127 * (1 - ($predict_threshold / 100));
                    $g = 255 * ($predict_threshold / 100);
                    $html->appendChild(DIV(
                        "Automatically selecting tags with greater than ",
                        SPAN(["style" => "background-color:rgba($r,$g,0,0.5)"], "$predict_threshold% probability")
                    ));
                } else {
                    $html->appendChild(DIV("Note: automatic tag predicting is currently active, this can be disabled in user options. You can also enable automatic tag selecting in user options.", ));
                }
                $enable_tagging = $enable_tagging ? "true" : "false";
                $html->appendChild(SCRIPT(
                    "const ENABLE_REVERSE_IMAGE = true;
                    const ENABLE_AUTO_TAG = $enable_tagging; 
                    const AUTO_TAG_THRESHOLD = $predict_threshold;"
                ));
                $page->add_block(new Block(null, $html, "main", 100));
            }
        } elseif ($event->page_matches("reverse_image_search", method: "GET")) {
            $this->theme->display_page();
        } elseif ($event->page_matches("reverse_image_search", method: "POST", authed: false)) {
            $ids = $this->reverse_image_search_post();
            if (count($ids) > 0) {
                $this->theme->display_page($_POST["reverse_image_limit"] ?? null);
                $this->theme->display_results($ids);
            } else {
                $page->set_redirect(make_link("reverse_image_search"));
                $page->flash("Something broke in the backed or no file or url given");
            }
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin();
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(PostAdminBlockBuildingEvent $event): void
    {
        $event->add_part(
            SHM_SIMPLE_FORM(
                make_link("reverse_image_search"),
                INPUT(["type" => "hidden", "name" => "hash", "value" => $event->image->hash]),
                INPUT([
                    "type" => "submit",
                    "value" => "Similar posts on this site",
                ])
            ),
            50
        );
    }

    #[EventListener]
    public function onImageInfoSet(PostInfoSetEvent $event): void
    {
        $exists = Ctx::$database->get_one("SELECT 1 FROM image_features WHERE image_id = :id", ["id" => $event->image->id]);
        if (is_null($exists)) {
            $features = $this->get_image_features($event->image->get_media_filename()->str());
            if ($features) {
                $this->add_features_to_db($features, $event->image->id);
            }
        }
    }

    #[EventListener]
    public function onImageReplace(MediaReplaceEvent $event): void
    {
        $exists = Ctx::$database->get_one("SELECT 1 FROM image_features WHERE image_id = :id", ["id" => $event->image->id]);
        $features = $this->get_image_features($event->tmp_filename->str());
        if ($features) {
            if ($exists) {
                $this->edit_features_to_db($features, $event->image->id);
            } else {
                $this->add_features_to_db($features, $event->image->id);
            }
        }
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
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
                $images = Ctx::$database->get_all($query, ["id" => $event->params['reverse_image_start_id'] ?: "0","limit" => $event->params['reverse_image_limit'] ?: "0"]);
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
                Log::info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    #[EventListener]
    public function onUploadAction(UploadActionEvent $event): void
    {
        $features = $this->get_image_features($event->file->str());
        if (!$features) {
            return;
        }
        $similarities = $this->reverse_image_compare($features, Ctx::$config->get(ReverseImageConfig::CONF_DEFAULT_AMOUNT));
        if (\count($similarities) > 0) {
            $event->output["tag_predictions"] = $this->tags_from_features_id($similarities);

            $first = array_key_first($similarities);
            $image = Post::by_id($first);
            if (!is_null($image)) {
                $event->output["visual_duplicate"] = [
                    "distance" => floor($similarities[$first] * 100),
                    "threshold" => 100 - Ctx::$config->get(ReverseImageConfig::SIMILARITY_DUPLICATE),
                    "image_id" => $first,
                    "image_data" => [
                        "link" => $image->get_media_link()->getPath(),
                        "thumb_link" => $image->get_thumb_link()->getPath(),
                        "width" => $image->width,
                        "height" => $image->height,
                        "filesize" => $image->filesize
                    ]
                ];
            }
        }
    }

    #[EventListener]
    public function onDuplicateCheck(DuplicateCheckEvent $event): void
    {
        if ($event->is_duplicate) {
            return;
        }
        $features = $this->get_image_features($event->file->str());
        if (!$features) {
            return;
        }

        $similarity = $this->get_similarty_from_post($event->image_id, $features);
        if ((100 * $similarity) <= (100 - Ctx::$config->get(ReverseImageConfig::SIMILARITY_DUPLICATE))) {
            $event->is_duplicate = true;
            $event->stop_processing = true;
        }

    }

    /**
     * @param array<int, float> $similarities
     * @return array<string, mixed>
     */
    public function tags_from_features_id(array $similarities): array
    {
        $ids = implode(",", array_keys($similarities));

        $sum_case = "SUM(CASE\n";
        $i = 1;
        foreach ($similarities as $id => $similarity) {
            $sum_case .= "WHEN b.image_id = $id THEN " . (1 - $similarity) / $i++ . "\n";
        }
        $sum_case .= "ELSE 0\nEND) AS n\n";
        $query = "SELECT a.tag,
            $sum_case
            FROM tags a
            INNER JOIN image_tags b ON a.id = b.tag_id
            WHERE b.image_id IN ($ids)
            GROUP BY a.tag
            ORDER BY n DESC";
        // @phpstan-ignore-next-line
        return Ctx::$database->get_pairs($query, []);
    }

    // adds features belonging to id to database
    /**
     * @param float[] $features
     */
    public function add_features_to_db(array $features, int $id): void
    {
        $feature_array = "[" . implode(",", $features) . "]";
        $query = "INSERT INTO image_features VALUES(:id,:feature_array)";
        $args = ["id" => $id,"feature_array" => $feature_array];
        Ctx::$database->execute($query, $args);
    }

    // edits features from an image
    /**
     * @param float[] $features
     */
    public function edit_features_to_db(array $features, int $id): void
    {
        $feature_array = "[" . implode(",", $features) . "]";
        $query = "UPDATE image_features SET features = :feature_array WHERE image_id = :id";
        $args = ["id" => $id,"feature_array" => $feature_array];
        Ctx::$database->execute($query, $args);
    }

    // downloads an image from a given url, returns the full image path
    /**
     * @param non-empty-string $url
     */
    private function transload(string $url): Path
    {
        $tmp_filename = shm_tempnam("transload");
        try {
            Network::fetch_url($url, $tmp_filename);
        } catch (FetchException $e) {
            throw new UploadException("Error reading from $url: $e");
        }
        return $tmp_filename;
    }

    // helper function for the default post request
    /**
     * @return array<int, mixed>
     */
    public function reverse_image_search_post(): array
    {
        if (isset($_POST["url"]) && $_POST["url"]) {
            $file = $this->transload($_POST["url"]);
        } elseif (isset($_POST["hash"]) && $_POST["hash"]) {
            $file = Filesystem::warehouse_path(Post::MEDIA_DIR, $_POST["hash"], false);
        } elseif (isset($_FILES['file'])) {
            if ($_FILES['file']['error']) {
                throw new UploadException("Upload failed: ".$_FILES['file']['error']);
            } else {
                $file = new Path($_FILES['file']['tmp_name']);
            }
        } else {
            return [];
        }

        $features = $this->get_image_features($file->str());

        if (isset($_POST["url"]) && $_POST["url"]) {
            unlink($file->str());
        }

        if (!$features) {
            return [];
        }
        $limit = isset($_POST["reverse_image_limit"]) ? $_POST["reverse_image_limit"] : Ctx::$config->get(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
        if ($limit > Ctx::$config->get(ReverseImageConfig::CONF_MAX_LIMIT)) {
            $limit = Ctx::$config->get(ReverseImageConfig::CONF_MAX_LIMIT);
        }
        return $this->reverse_image_compare($features, $limit);
    }

    // helper function
    /**
     * @return array<float>|false
     * @param hash-string $hash
     */
    public function get_image_features_by_hash(string $hash): array|false
    {
        return $this->get_image_features($_SERVER['DOCUMENT_ROOT'] ."/" . Filesystem::warehouse_path(Post::MEDIA_DIR, $hash)->str());
    }

    // makes the post request to the engine.py, returns the features as array[512] or false if it failed
    /**
     * @return array<float>|false
     */
    public function get_image_features(string $path): array|false
    {
        $uri = Ctx::$config->get(ReverseImageConfig::CONF_URL);
        $url = "$uri/extract_features";
        $ch = curl_init($url);
        assert($ch !== false);
        if (function_exists('curl_file_create')) { // php 5.5+
            $cFile = curl_file_create($path);
        } else {
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
        $uri = Ctx::$config->get(ReverseImageConfig::CONF_URL);
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
     * @return array<int, float>
     */
    private function reverse_image_compare(array $features, int|string $limit, ?int $offset = 0): array
    {
        $feature_array = "[" . implode(",", $features) . "]";
        return Ctx::$database->get_pairs(
            "SELECT 
            image_id, features <=> :feature_array AS similarity
            FROM image_features
            ORDER BY similarity ASC
            LIMIT :limit
            OFFSET :offset",
            [
                "feature_array" => $feature_array,
                "limit" => $limit,
                "offset" => $offset
            ]
        );
    }

    /**
     * @param float[] $features
     */
    private function get_similarty_from_post(int $image_id, array $features): float
    {
        $feature_array = "[" . implode(",", $features) . "]";
        return Ctx::$database->get_one(
            "SELECT features <=> :feature_array 
            FROM image_features
            WHERE image_id = :image_id",
            [
                "feature_array" => $feature_array,
                "image_id" => $image_id
            ]
        );
    }
}
