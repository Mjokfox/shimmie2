<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{H2, B, LABEL, DIV, TABLE, TR, TD, INPUT, rawHTML, emptyHTML, IMG};

class ReverseImageTheme extends Themelet
{
    public function build_navigation(string $search_string = "", string $class = ""): HTMLElement
    {
        $h_search_link = make_link("post/search/1");
        return rawHTML("
			<form action='$h_search_link' method='GET' class='search-bar $class'>
				<input name='search' type='text' value='$search_string' class='text-search' placeholder='text'/>
				<input type='submit' value='Go!'>
				<input type='hidden' name='q' value='post/list'>
			</form>
		");
    }

    public function list_search(Page $page, string $search = ""): void
    {
        $nav = $this->build_navigation($search, "full-width");
        $page->add_block(new Block("Text Search", $nav, "left", 2, "text-search"));
    }

    public function view_search(Page $page, string $search = ""): void
    {
        $nav = $this->build_navigation($search, "");
        $page->add_block(new Block("Text Search", $nav, "left", 2, "text-search-view"));
    }
    public function display_page(string|null $r_i_l = null): void
    {
        global $page, $config;
        $max_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT);
        $default_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);
        $url = $_POST["url"] ?? "";
        $html = SHM_FORM("reverse_image_search", multipart: true, form_id: "reverse_image_search");
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
        $table = "<div class='shm-image-list'>";
        foreach (array_keys($ids) as $id) {
            $similarity = 100 * round(1 - $ids[$id], 2);
            $image = Image::by_id($id);
            if ($image) {
                $table .= LABEL(
                    "Similarity: $similarity%",
                    $this->build_thumb($image)
                );
            }
        }
        $table .= "</div>";
        $html->appendChild(rawHTML($table));
        $page->add_block(new Block(null, $html, "main", 20));
    }

    public function display_admin(): void
    {
        global $page;
        $html = (string)SHM_SIMPLE_FORM(
            "admin/reverse_image",
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
        $page->add_block(new Block("Extract Features", rawHTML($html)));
    }
}
