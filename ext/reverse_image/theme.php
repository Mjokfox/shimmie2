<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{H2, A,B, LABEL, BR, DIV, TABLE, TR, TD, INPUT, rawHTML, emptyHTML, IMG};

class ReverseImageTheme extends Themelet
{
    public function display_page($r_i_l=null,$url=null): void
    {
        global $page, $config;
        $max_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_MAX_LIMIT);
        $default_reverse_result_limit = $config->get_int(ReverseImageConfig::CONF_DEFAULT_AMOUNT);

        $html = SHM_FORM("reverse_image_search", multipart: true, form_id: "reverse_image_search");
        $html->appendChild(
            DIV(["class" =>" RIS-spacer"],
            DIV(["class" => "RIS-container"],
            H2("Search for similar images on this website!"),
            DIV(["id" => "dropZone","class" => "RIS-subcontainer"],
                DIV(["class" => "RIS-file-block"],
                    INPUT([
                        "type" => "file",
                        "id" => "file_input",
                        "name" => "file",
                        "accept" => "image/*",
                        "multiple" => false,
                        "style" => "display:none",
                    ]),
                    DIV(["style" => "margin:0 auto;"],
                    B(["style" => "padding-right:0.5em;"],"Drag an image or"),
                    B([
                        "id" => "browse_button",
                        "class" => "RIS-browse",
                        "onclick" => "document.getElementById('file_input').click();"
                    ],
                    "upload a file"),
                    )
                ),
                DIV(["class" => "RIS-group"],
                    INPUT([
                        "type" => "text",
                        "id" => "url_input",
                        "name" => "url_input",
                        "value" => $url ?: "",
                        "style" => "flex-grow:1; width:100%; padding:2px 10px;",
                        "placeholder" => "Or paste image url"
                    ]),
                    
                    SHM_SUBMIT('Search!',["id" => "submit_button", "style" => "padding:2px;"]),
                ),
                DIV(["class" => "RIS-group","style" => "justify-content: right;"],
                    B(["style" => "margin-right:0.5em;"],"Amount of results:"),
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

    public function display_results($ids,$original_image_path,$image_url): void
    {
        global $page;
        if ($image_url){
            $src = $image_url;
        } else{
            $fileType = mime_content_type($original_image_path);
            $imageData = file_get_contents($original_image_path);
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
            $similarity = 100*round(1 - $ids[$id],2);
            $table .= LABEL("Similarity: $similarity%",
            $this->build_thumb_html(Image::by_id($id)));
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
                TD(["style" => "padding-right:5px"],B("Start id")),TD(INPUT(["type" => 'number', "name" => 'reverse_image_start_id', "value" => "0", "style" => "width:5em"])),
            ),
            TR(
                TD(B("Limit")),TD(INPUT(["type" => 'number', "name" => 'reverse_image_limit', "value" => "100", "style" => "width:5em"])),
            ),
        ),
            SHM_SUBMIT('Extract features into database'),
            
        );
        $page->add_block(new Block("Extract Features", rawHTML($html)));
    }
}
