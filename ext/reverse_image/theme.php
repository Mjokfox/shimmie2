<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{H2, B, LABEL, DIV, TABLE, TR, TD, INPUT, emptyHTML, IMG, FORM};

class ReverseImageTheme extends Themelet
{
    public function build_navigation(string $search_string = "", string $class = ""): HTMLElement
    {
        global $user;
        $action = make_link("post/search/1");
        return FORM(
            [
                "action" => $action,
                "method" => "GET",
                "class" => "search-bar $class"
            ],
            INPUT(["type" => "hidden", "name" => "q", "value" => $action->getPath()]),
            INPUT(["type" => "hidden", "name" => "auth_token", "value" => $user->get_auth_token()]),
            INPUT([
                "name" => 'search',
                "type" => 'text',
                "class" => 'text-search',
                "placeholder" => 'text',
                "value" => $search_string
            ]),
            SHM_SUBMIT("Go!"),
        );
    }

    public function list_search(string $search = ""): void
    {
        global $page;
        $nav = $this->build_navigation($search, "full-width");
        $page->add_block(new Block("Text Search", $nav, "left", 2, "text-search"));
    }

    public function view_search(string $search = ""): void
    {
        global $page;
        $nav = $this->build_navigation($search, "");
        $page->add_block(new Block("Text Search", $nav, "left", 2, "text-search-view"));
    }
    public function display_page(string|null $r_i_l = null): void
    {
        global $page, $config;
        $max_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT);
        $default_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
        $url = $_POST["url"] ?? "";
        $html = SHM_FORM(make_link("reverse_image_search"), multipart: true, id: "reverse_image_search");
        $html->appendChild(
            DIV(
                ["class" => " RIS-spacer"],
                DIV(
                    ["class" => "RIS-container"],
                    H2("Search for similar images on this website!"),
                    DIV(
                        ["id" => "dropZone","class" => "RIS-subcontainer"],
                        DIV(
                            ["class" => "RIS-file-block"],
                            INPUT([
                                "type" => "file",
                                "id" => "file_input",
                                "name" => "file",
                                "accept" => "image/*",
                                "multiple" => false,
                                "style" => "display:none",
                            ]),
                            DIV(
                                ["style" => "margin:0 auto;"],
                                B(["style" => "padding-right:0.5em;"], "Drag an image or"),
                                B(
                                    [
                                    "id" => "browse_button",
                                    "class" => "RIS-browse",
                                    "onclick" => "document.getElementById('file_input').click();"
                    ],
                                    "upload a file"
                                ),
                            )
                        ),
                        DIV(
                            ["class" => "RIS-group"],
                            INPUT([
                                "type" => "text",
                                "id" => "url_input",
                                "name" => "url",
                                "value" => $url ?: "",
                                "style" => "flex-grow:1; width:100%; padding:2px 10px;",
                                "placeholder" => "Or paste image url"
                            ]),
                            SHM_SUBMIT('Search!', ["id" => "submit_button", "style" => "padding:2px;"]),
                        ),
                        DIV(
                            ["class" => "RIS-group","style" => "justify-content: right;"],
                            B(["style" => "margin-right:0.5em;"], "Amount of results:"),
                            INPUT([
                                "type" => "number",
                                "name" => "reverse_image_limit",
                                "value" => $r_i_l ?: $default_reverse_result_limit,
                                "min" => "1",
                                "max" => (string)$max_reverse_result_limit,
                                "style" => "margin-right:0.5em;"
                            ]),
                            B("(max $max_reverse_result_limit)"),
                        ),
                    )
                )
            )
        );
        $page->add_block(new Block(null, $html, "main", 20));
    }

    /**
     * @param int[] $ids
     */
    public function display_results(array $ids): void
    {
        global $page;
        $src = null;
        if ((isset($_POST["url"]) && $_POST["url"])) {
            $src = $_POST["url"];
        } elseif (isset($_POST["hash"]) && $_POST["hash"]) {
            $image = Image::by_hash($_POST["hash"]);
            if ($image) {
                $src = $image->get_image_link();
            }
        } elseif (isset($_FILES['file'])) {
            $fileType = mime_content_type($_FILES['file']['tmp_name']);
            $imageData = file_get_contents($_FILES['file']['tmp_name']);
            if (!$imageData) {
                throw new ServerError("Your input image got lost somehow, please try again");
            }
            $src = 'data:' . $fileType . ';base64,' . base64_encode($imageData);
        }
        $html = emptyHTML();
        $html->appendChild(
            H2("Your image:"),
            IMG(["src" => $src,"alt" => "Uploaded image.","style" => "max-height:512px;max-width:512px;"]),
            H2("Visually similar images on this site:"),
        );
        $table = DIV(["class" => 'shm-image-list']);
        foreach (array_keys($ids) as $id) {
            $similarity = 100 * round(1 - $ids[$id], 2);
            $image = Image::by_id($id);
            if ($image) {
                $table->appendChild(LABEL(
                    "Similarity: $similarity%",
                    $this->build_thumb($image)
                ));
            }
        }
        $html->appendChild($table);
        $page->add_block(new Block(null, $html, "main", 20));
    }

    public function display_admin(): void
    {
        global $page;
        $html = SHM_SIMPLE_FORM(
            make_link("admin/reverse_image"),
            TABLE(
                TR(
                    TD(["style" => "padding-right:5px"], B("Start id")),
                    TD(INPUT(["type" => 'number', "name" => 'reverse_image_start_id', "value" => "0", "style" => "width:5em"])),
                ),
                TR(
                    TD(B("Limit")),
                    TD(INPUT(["type" => 'number', "name" => 'reverse_image_limit', "value" => "100", "style" => "width:5em"])),
                ),
            ),
            SHM_SUBMIT('Extract features into database'),
        );
        $page->add_block(new Block("Extract Features", $html));
    }
}
