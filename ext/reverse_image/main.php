<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

require_once "config.php";

class ReverseImage extends Extension
{
    /** @var ReverseImageTheme */
    protected Themelet $theme;
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(ReverseImageConfig::CONF_MAX_LIMIT, 10);
        $config->set_default_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT, 5);
        $config->set_default_string(ReverseImageConfig::CONF_URL, "127.0.0.1:10017");
    }
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
    public function onInitUserConfig(InitUserConfigEvent $event): void
    {

        $event->user_config->set_default_bool(ReverseImageConfig::USER_ENABLE_AUTO, true);
        $event->user_config->set_default_bool(ReverseImageConfig::USER_ENABLE_AUTO_SELECT, false);
        $event->user_config->set_default_int(ReverseImageConfig::USER_TAG_THRESHOLD, 50);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link("reverse image search", new Link('reverse_image_search'), "Reverse Image Search",order:51);
        }
    }
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page;
        if ($event->page_matches("reverse_image_search", method: "GET")) {
            $this->theme->display_page();
        } else if ($event->page_matches("reverse_image_search", method: "POST", authed: false)) {
            $ids = $this->reverse_image_search_post();
            if (count($ids) > 0){
                $this->theme->display_page($_POST["reverse_image_limit"],$_POST["url_input"]);
                $this->theme->display_results($ids,$_FILES['file']['tmp_name'],$_POST["url_input"]);
            }
            else {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect("reverse_image_search");
                $page->flash("No file or url given");
            }
        } else if ($event->page_matches("reverse_image_search_fromupload", method: "POST", authed: false)) {
            $ids = $this->reverse_image_search_post();
            $page->set_mode(PageMode::DATA);
            if (count($ids) > 0){
                $tag_n = $this->tags_from_features_id($ids);
                $page->set_data(json_encode($tag_n));
                $page->set_filename('tag_occurrences.json','Content-Type: application/json');
            }
            else {
                $page->set_data(json_encode(["No similar images found, either the file was not uploaded properly or no url given"]));
                $page->set_filename('failed.json','Content-Type: application/json');
            }
        } else if ($event->page_matches("upload", method: "GET", permission: Permissions::CREATE_IMAGE)) {
            global $config, $user_config;
            $default_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
            $enable_auto_pre = $user_config->get_bool(ReverseImageConfig::USER_ENABLE_AUTO);
            $enable_auto_tag = $user_config->get_bool(ReverseImageConfig::USER_ENABLE_AUTO_SELECT);
            $predict_threshold = $user_config->get_int(ReverseImageConfig::USER_TAG_THRESHOLD);
            $html = "";
            if ($enable_auto_tag){
                $r = 127*(1-($predict_threshold/100));
                $g = 255*($predict_threshold/100);
                $man = $enable_auto_pre ? "Automatically" : "Semi manually";
                $html .= "<div>$man selecting tags with higher than <div, style='background-color:rgba($r,$g,0,0.5)'> $predict_threshold% [predicted] probability</div></div>";
            } else if ($enable_auto_pre){
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
        }
    }
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Reverse image search");
        $sb->add_int_option(ReverseImageConfig::CONF_MAX_LIMIT, "Maximum reverse image search results: ");
        $sb->add_int_option(ReverseImageConfig::CONF_DEFAULT_AMOUNT, "<br/>Default reverse image search results: ");
        $sb->add_text_option(ReverseImageConfig::CONF_URL, "<br/>Python engine url: ");
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin();
    }
    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Reverse image search");
        $sb->add_bool_option(ReverseImageConfig::USER_ENABLE_AUTO, 'Enable automatic predicting: ');
        $sb->add_bool_option(ReverseImageConfig::USER_ENABLE_AUTO_SELECT, '<br>Enable automatic tagging: ');
        $sb->add_int_option(ReverseImageConfig::USER_TAG_THRESHOLD, '<br>The minimum percentage prediction to tag: ');
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch($event->action) {
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
                $images = $database->get_all($query,["id" => $event->params['reverse_image_start_id'] | "0","limit" => $event->params['reverse_image_limit'] | "0"]);
                $i = 0;
                $j = [];
                foreach ($images as $image) {
                    $features = $this->get_image_features_by_hash($image["hash"]);
                    if (!$features){
                        $j[] = $image["id"];
                    }
                    else {
                        $this->add_features_to_db($features,$image["id"]);
                        $i++;
                    }
                }
                $ids = implode(",",$j);
                $exec_time = round(ftime() - $start_time, 2);
                $message = "Added image features to the database for $i images in $exec_time seconds" . (count($j) > 0 ? ", but failed for image ids [$ids]" : ".");
                log_info("admin", $message, $message);
                $event->redirect = true;
                break;
        }
    }

    public function tags_from_features_id($ids):array
    {
        global $database;
        $ids_array = implode(",",array_keys($ids));
        
        $sum_case = "SUM(CASE\n";
        $i = 1;
        foreach(array_keys($ids) as $id){
            $sum_case .= "WHEN b.image_id = $id THEN " . (1-$ids[$id])/$i++ . "\n";
        }
        $sum_case .= "ELSE 0\nEND) AS n\n";
        $query = "SELECT a.tag,
            $sum_case
            FROM tags a
            INNER JOIN image_tags b ON a.id = b.tag_id
            WHERE b.image_id IN ($ids_array)
            GROUP BY a.tag
            ORDER BY n DESC";
        
        return $database->get_pairs($query,[]);
    }

    // adds features belonging to id to database
    public function add_features_to_db($features,$id): void
    {
        global $database;
        $feature_array = "[" . implode(",",$features) . "]";
        $query = "INSERT INTO image_features VALUES(:id,:feature_array)";
        $args = ["id" => $id,"feature_array" => $feature_array];
        $database->execute($query,$args);
    }

    // downloads an image from a given url, returns the full image path
    private function transload($url): string | null
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
    public function reverse_image_search_post(): array
    {
        global $page, $config;
        if (isset($_POST["url_input"]) && $_POST["url_input"]){
            $file = $this->transload($_POST["url_input"]);
        }
        else if (isset($_FILES['file'])) {
            if($_FILES['file']['error']){
                throw new UploadException("Upload went wrong in ext: reverse_image_serach, code ".$_FILES['file']['error']);
            } else{
                $file = $_FILES['file']['tmp_name']; 
            }
        } else return [];

        $features = $this->get_image_features($file);

        if (!$features) return [];
        $limit = isset($_POST["reverse_image_limit"]) ? $_POST["reverse_image_limit"] : $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
        if ($limit > $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT)) $limit = $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT);
        return $this->reverse_image_compare($features,$limit);
        
    }

    // helper function
    public function get_image_features_by_hash($hash): array | bool
    {
        return $this->get_image_features($_SERVER['DOCUMENT_ROOT'] ."/" . warehouse_path(Image::IMAGE_DIR, $hash));
    }

    // makes the post request to the engine.py, returns the features as array[512] or false if it failed
    public function get_image_features($path): array|bool
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
        $post = array('image'=> $cFile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $result=curl_exec($ch);
        curl_close($ch);

        $json = json_decode($result,true);

        if (!isset($json["features"])) return false;
        return $json["features"];

    }

    // gets the closest image ids from the input features, returning an array[$limit] of these ids
    private function reverse_image_compare($features,$limit): array
    {
        global $database;
        $feature_array = "[" . implode(",",$features) . "]";
        $query = "SELECT image_id, features <=> :feature_array AS similarity
            FROM image_features
            ORDER BY similarity ASC
            LIMIT :limit;";
        $args = ["feature_array" => $feature_array, "limit" => $limit];
        $image_ids = $database->get_pairs($query, $args);
        return $image_ids;
    }
}
