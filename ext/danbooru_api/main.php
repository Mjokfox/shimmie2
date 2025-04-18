<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @param mixed[] ...$args
 */
function TAGS(...$args): HTMLElement
{
    return new HTMLElement("tags", $args);
}
/**
 * @param mixed[] ...$args
 */
function TAG(...$args): HTMLElement
{
    return new HTMLElement("tag", $args);
}
/**
 * @param mixed[] ...$args
 */
function POSTS(...$args): HTMLElement
{
    return new HTMLElement("posts", $args);
}
/**
 * @param mixed[] ...$args
 */
function POST(...$args): HTMLElement
{
    return new HTMLElement("post", $args);
}


final class DanbooruApi extends Extension
{
    public const KEY = "danbooru_api";

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        if ($event->page_matches("api/danbooru/add_post") || $event->page_matches("api/danbooru/post/create.xml")) {
            // No XML data is returned from this function
            $page->set_data(MimeType::TEXT, "");
            $this->api_add_post($event);
        } elseif ($event->page_matches("api/danbooru/find_posts") || $event->page_matches("api/danbooru/post/index.xml")) {
            $page->set_data(MimeType::XML_APPLICATION, (string)$this->api_find_posts($event));
        } elseif ($event->page_matches("api/danbooru/find_tags")) {
            $page->set_data(MimeType::XML_APPLICATION, (string)$this->api_find_tags($event));
        }

        // Hackery for danbooruup 0.3.2 providing the wrong view url. This simply redirects to the proper
        // Shimmie view page
        // Example: danbooruup says the url is https://shimmie/api/danbooru/post/show/123
        // This redirects that to https://shimmie/post/view/123
        elseif ($event->page_matches("api/danbooru/post/show/{id}")) {
            $fixedlocation = make_link("post/view/" . $event->get_iarg('id'));
            $page->set_redirect($fixedlocation);
        }
    }

    /**
     * Turns out I use this a couple times so let's make it a utility function
     * Authenticates a user based on the contents of the login and password parameters
     * or makes them anonymous. Does not set any cookies or anything permanent.
     */
    private function authenticate_user(PageRequestEvent $event): void
    {
        if ($event->POST->get('login') && $event->POST->get('password')) {
            // Get this user from the db, if it fails the user becomes anonymous
            // Code borrowed from /ext/user
            try {
                $name = $event->POST->req('login');
                $pass = $event->POST->req('password');
                Ctx::$user = User::by_name_and_pass($name, $pass);
            } catch (UserNotFound $e) {
                Ctx::$user = User::get_anonymous();
            }
            send_event(new UserLoginEvent(Ctx::$user));
        }
    }

    /**
     * find_tags()
     * Find all tags that match the search criteria.
     *
     * Parameters
     * - id: A comma delimited list of tag id numbers.
     * - name: A comma delimited list of tag names.
     * - tags: any typical tag query. See Tag#parse_query for details.
     * - after_id: limit results to tags with an id number after after_id. Useful if you only want to refresh
     */
    private function api_find_tags(PageRequestEvent $event): HTMLElement
    {
        global $database;
        $params = $event->GET;

        $results = [];
        if (isset($params['id'])) {
            $idlist = explode(",", $params['id']);
            foreach ($idlist as $id) {
                $sqlresult = $database->get_all(
                    "SELECT id,tag,count FROM tags WHERE id = :id",
                    ['id' => $id]
                );
                foreach ($sqlresult as $row) {
                    $results[] = [$row['count'], $row['tag'], $row['id']];
                }
            }
        } elseif (isset($params['name'])) {
            $namelist = explode(",", $params['name']);
            foreach ($namelist as $name) {
                $sqlresult = $database->get_all(
                    "SELECT id,tag,count FROM tags WHERE LOWER(tag) = LOWER(:tag)",
                    ['tag' => $name]
                );
                foreach ($sqlresult as $row) {
                    $results[] = [$row['count'], $row['tag'], $row['id']];
                }
            }
        }
        // Currently disabled to maintain identical functionality to danbooru 1.0's own "broken" find_tags
        /*
        elseif (isset($params['tags'])) {
            $start = isset($params['after_id']) ? int_escape($params['offset']) : 0;
            $tags = Tag::explode($params['tags']);
            assert(!is_null($start) && !is_null($tags));
        }
        */ else {
            $start = isset($params['after_id']) ? int_escape($params['offset']) : 0;
            $sqlresult = $database->get_all(
                "SELECT id,tag,count FROM tags WHERE count > 0 AND id >= :id ORDER BY id DESC",
                ['id' => $start]
            );
            foreach ($sqlresult as $row) {
                $results[] = [$row['count'], $row['tag'], $row['id']];
            }
        }

        // Tag results collected, build XML output
        $xml = TAGS();
        foreach ($results as $tag) {
            $xml->appendChild(TAG([
                "type" => "0",
                "counts" => $tag[0],
                "name" => $tag[1],
                "id" => $tag[2],
            ]));
        }
        return $xml;
    }

    /**
     * find_posts()
     * Find all posts that match the search criteria. Posts will be ordered by id descending.
     *
     * Parameters:
     * - md5: md5 hash to search for (comma delimited)
     * - id: id to search for (comma delimited)
     * - tags: what tags to search for
     * - limit: limit
     * - page: page number
     * - after_id: limit results to posts added after this id
     */
    private function api_find_posts(PageRequestEvent $event): HTMLElement
    {
        $params = $event->GET;
        $results = [];

        $this->authenticate_user($event);
        $start = 0;

        if (isset($params['md5'])) {
            $md5list = explode(",", $params['md5']);
            foreach ($md5list as $md5) {
                assert($md5 !== '');
                $results[] = Image::by_hash($md5);
            }
            $count = count($results);
        } elseif (isset($params['id'])) {
            $idlist = explode(",", $params['id']);
            foreach ($idlist as $id) {
                $results[] = Image::by_id(int_escape($id));
            }
            $count = count($results);
        } else {
            $limit = isset($params['limit']) ? int_escape($params['limit']) : 100;

            // Calculate start offset.
            if (isset($params['page'])) { // Danbooru API uses 'page' >= 1
                $start = (int_escape($params['page']) - 1) * $limit;
            } elseif (isset($params['pid'])) { // Gelbooru API uses 'pid' >= 0
                $start = int_escape($params['pid']) * $limit;
            } else {
                $start = 0;
            }

            $tags = isset($params['tags']) ? Tag::explode($params['tags']) : [];
            // danbooru API clients often set tags=*
            $tags = array_filter($tags, static function ($element) {
                return $element !== "*";
            });
            $tags = array_values($tags); // reindex array because count_images() expects a 0-based array
            $count = Search::count_images($tags);
            $results = Search::find_images(max($start, 0), min($limit, 100), $tags);
        }

        // Now we have the array $results filled with Image objects
        // Let's display them
        $xml = POSTS(["count" => $count, "offset" => $start]);
        foreach ($results as $img) {
            // Sanity check to see if $img is really an image object
            // If it isn't (e.g. someone requested an invalid md5 or id), break out of the this
            if (!is_object($img)) {
                continue;
            }
            $taglist = $img->get_tag_list();
            $owner = $img->get_owner();
            $previewsize = $img->get_thumb_size();
            $xml->appendChild(TAG([
                "id" => $img->id,
                "md5" => $img->hash,
                "file_name" => $img->filename,
                "file_url" => $img->get_image_link(),
                "height" => $img->height,
                "width" => $img->width,
                "preview_url" => $img->get_thumb_link(),
                "preview_height" => $previewsize[1],
                "preview_width" => $previewsize[0],
                "rating" => "?",
                "date" => $img->posted,
                "is_warehoused" => false,
                "tags" => $taglist,
                "source" => $img->source,
                "score" => 0,
                "author" => $owner->name
            ]));
        }
        return $xml;
    }

    /**
     * add_post()
     * Adds a post to the database.
     *
     * Parameters:
     * - login: login
     * - password: password
     * - file: file as a multipart form
     * - source: source url
     * - title: title **IGNORED**
     * - tags: list of tags as a string, delimited by whitespace
     * - md5: MD5 hash of upload in hexadecimal format
     * - rating: rating of the post. can be explicit, questionable, or safe. **IGNORED**
     *
     * Notes:
     * - The only necessary parameter is tags and either file or source.
     * - If you want to sign your post, you need a way to authenticate your account, either by supplying login and password, or by supplying a cookie.
     * - If an account is not supplied or if it doesnt authenticate, he post will be added anonymously.
     * - If the md5 parameter is supplied and does not match the hash of whats on the server, the post is rejected.
     *
     * Response
     * The response depends on the method used:
     * Post:
     * - X-Danbooru-Location set to the URL for newly uploaded post.
     * Get:
     * - Redirected to the newly uploaded post.
     */
    private function api_add_post(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;

        // Check first if a login was supplied, if it wasn't check if the user is logged in via cookie
        // If all that fails, it's an anonymous upload
        $this->authenticate_user($event);
        // Now we check if a file was uploaded or a url was provided to transload
        // Much of this code is borrowed from /ext/upload

        if (!Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: authentication error");
            return;
        }

        if (isset($_FILES['file'])) {    // A file was POST'd in
            $file = new Path($_FILES['file']['tmp_name']);
            $filename = $file->basename()->str();
            // If both a file is posted and a source provided, I'm assuming source is the source of the file
            if (isset($_REQUEST['source']) && !empty($_REQUEST['source'])) {
                $source = $_REQUEST['source'];
            } else {
                $source = null;
            }
        } elseif (isset($_FILES['post'])) {
            $file = new Path($_FILES['post']['tmp_name']['file']);
            $filename = $file->basename()->str();
            if (isset($_REQUEST['post']['source']) && !empty($_REQUEST['post']['source'])) {
                $source = $_REQUEST['post']['source'];
            } else {
                $source = null;
            }
        } elseif (isset($_REQUEST['source']) || isset($_REQUEST['post']['source'])) {    // A url was provided
            $source = isset($_REQUEST['source']) ? $_REQUEST['source'] : $_REQUEST['post']['source'];
            $file = shm_tempnam("transload");
            try {
                Network::fetch_url($source, $file);
            } catch (FetchException $e) {
                $page->set_code(409);
                $page->add_http_header("X-Danbooru-Errors: $e");
                return;
            }
            $filename = basename($source);
        } else {    // Nothing was specified at all
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: no input files");
            return;
        }

        // Get tags out of url
        $posttags = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : $_REQUEST['post']['tags'];

        // Was an md5 supplied? Does it match the file hash?
        $hash = $file->md5();
        if (isset($_REQUEST['md5']) && strtolower($_REQUEST['md5']) !== $hash) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: md5 mismatch");
            return;
        }
        // Upload size checking is now performed in the upload extension
        // It is also currently broken due to some confusion over file variable ($tmp_filename?)

        // Does it exist already?
        $existing = Image::by_hash($hash);
        if (!is_null($existing)) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: duplicate");
            $existinglink = make_link("post/view/" . $existing->id)->asAbsolute();
            $page->add_http_header("X-Danbooru-Location: $existinglink");
            return;
        }

        //Log::debug("danbooru_api","========== NEW($filename) =========");
        //Log::debug("danbooru_api", "upload($filename): fileinfo(".var_export($fileinfo,TRUE)."), metadata(".var_export($metadata,TRUE).")...");

        try {
            $newimg = $database->with_savepoint(function () use ($file, $filename, $posttags, $source) {
                // Fire off an event which should process the new file and add it to the db
                $dae = send_event(new DataUploadEvent($file, $filename, 0, new QueryArray([
                    'tags' => $posttags,
                    'source' => $source,
                ])));

                //Log::debug("danbooru_api", "send_event(".var_export($nevent,TRUE).")");
                // If it went ok, grab the id for the newly uploaded image and pass it in the header
                return $dae->images[0];
            });

            $newid = make_link("post/view/" . $newimg->id)->asAbsolute();

            // Did we POST or GET this call?
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $page->add_http_header("X-Danbooru-Location: $newid");
            } else {
                $page->add_http_header("Location: $newid");
            }
        } catch (UploadException $ex) {
            $page->set_code(409);
            $page->add_http_header("X-Danbooru-Errors: exception - " . $ex->getMessage());
        }
    }
}
