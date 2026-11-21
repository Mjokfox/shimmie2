<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, BUTTON, DIV, INPUT, NOSCRIPT, OPTION, P, SELECT, SMALL, SPAN, TEXTAREA, emptyHTML, rawHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE, TD, TH, TR};

class UploadTheme extends Themelet
{
    public function display_block(): void
    {
        Ctx::$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20, is_content: false));
    }

    public function display_full(): void
    {
        Ctx::$page->add_block(new Block("Upload", emptyHTML("Disk nearly full, uploads disabled"), "left", 20));
    }

    public function display_page(): void
    {
        $limits = get_upload_limits();

        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = $limits['shm_post'];
        $max_total_kb = to_shorthand_int($max_total_size);
        $upload_list = $this->build_upload_list();

        $common_fields = emptyHTML();
        $ucbe = send_event(new UploadCommonBuildingEvent());
        foreach ($ucbe->get_parts() as $part) {
            $common_fields->appendChild($part);
        }
        $captcha = Captcha::get_html(UploadPermission::SKIP_UPLOAD_CAPTCHA);

        $form = SHM_FORM(
            make_link("upload"),
            multipart: true,
            id: "file_upload",
            extra_attrs: [
                "data-max-size" => $max_size,
                "data-max-total-size" => $max_total_size,
            ],
        );
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form", "class" => "form upload-form"],
                $common_fields,
                $upload_list,
                $captcha ? TR(
                    TD(["colspan" => "7"], $captcha)
                ) : null,
                TR(
                    TD(["colspan" => "7"], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Post"]))
                ),
            )
        );
        $html = emptyHTML(
            DIV(
                ["style" => "width: 50%;display: flex;justify-content: center;"],
                B(
                    ["style" => "border: 2px outset var(--text);padding:3px 1rem;"],
                    "Before uploading, please read the ",
                    A(["href" => "/wiki/upload_guidelines"], "guidelines")
                ),
            ),
            $form,
            SMALL(
                "(",
                $max_size > 0 ? "File limit $max_kb" : null,
                $max_size > 0 && $max_total_size > 0 ? " / " : null,
                $max_total_size > 0 ? "Total limit $max_total_kb" : null,
                " / Current total ",
                SPAN(["id" => "upload_size_tracker"], "0KB"),
                ")"
            ),
        );

        $json_data = cache_get_or_set("upload_page_json_data", function () use ($max_size, $max_total_size, $ucbe) {
            $uhbe = send_event(new UploadHeaderBuildingEvent());
            $usfbe = send_event(new UploadSpecificBuildingEvent(""));

            $upload_page_data = [
                "max_panels" => Ctx::$config->get(UploadConfig::COUNT),
                "preview_enabled" => Ctx::$config->get(UploadConfig::PREVIEW),
                "splitview" => Ctx::$config->get(UploadConfig::SPLITVIEW),
                "separator_tag" => Ctx::$config->get(UploadConfig::SEPARATOR_TAG),
                "transload" => Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none",
                "media_accept" => $this->get_accept(),
                "common_parts" => array_map(fn ($e) => $e->__toString(), array_values($ucbe->get_parts())),
                "specific_headers" => $uhbe->get_parts(),
                "specific_parts" => array_map(fn ($e) => $e->__toString(), array_values($usfbe->get_parts())),
                "max_file_size" => $max_size,
                "max_total_size" => $max_total_size
                ];


            $blocks = array_map(function ($b) {
                $tags = explode(" ", $b["tags"]);
                $combined = [];
                foreach ($tags as $tag) {
                    $aliases = AliasEditor::get_aliases($tag);
                    $combined[] = ["tag" => $tag, "alias" => empty($aliases) ? $tag : $aliases[0]];
                }
                return ["name" => $b["name"], "type" => $b["type"], "tags" => $combined];
            }, Upload::get_blocks());

            $type_table = [1 => ["cols" => 2, "class" => "grid-cell"],
            2 => ["cols" => 4, "class" => "grid-cell cell-wide"],
            3 => ["cols" => 1, "class" => "grid-cell cell-thin"],
            4 => ["cols" => 1, "class" => "grid-cell cell-thin"],
            5 => ["cols" => 4, "class" => "grid-cell cell-wide"],
            6 => ["cols" => 2, "class" => "grid-cell"],
            7 => ["cols" => 4, "class" => "grid-cell cell-info"],
            8 => ["cols" => 4, "class" => "grid-cell cell-wide"],
            9 => ["cols" => 4, "class" => "grid-cell cell-wide"],];

            return json_encode([
                "upload_page_data" => $upload_page_data,
                "type_table" => $type_table,
                "blocks" => $blocks
                ], );
        }, 300);

        Ctx::$page->add_html_header(rawHTML(
            "<script id=\"upload_page_data\" type=\"application/json\">$json_data</script>"
        )); // must be rawhtml, otherwise html encoding breaks it

        Ctx::$page->set_title("Upload");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Upload", $html, "main", 20));
        if ($tl_enabled) {
            Ctx::$page->add_block(new Block("Bookmarklets", $this->build_bookmarklets(), "left", 20));
        }
    }

    protected function build_upload_list(): HTMLElement
    {
        $upload_list = emptyHTML();
        $upload_count = Ctx::$config->get(UploadConfig::COUNT);
        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $accept = $this->get_accept();

        $headers = emptyHTML();
        $uhbe = send_event(new UploadHeaderBuildingEvent());
        foreach ($uhbe->get_parts() as $part) {
            $headers->appendChild(
                TH("Post-Specific $part")
            );
        }

        $upload_list->appendChild(
            TR(
                ["class" => "header"],
                TH(["colspan" => 2], "Select File"),
                TH($tl_enabled ? "or URL" : null),
                $headers,
            )
        );

        for ($i = 0; $i < $upload_count; $i++) {
            $specific_fields = emptyHTML();
            $usfbe = send_event(new UploadSpecificBuildingEvent((string)$i));
            foreach ($usfbe->get_parts() as $part) {
                $specific_fields->appendChild($part);
            }

            $upload_list->appendChild(
                TR(
                    TD(
                        ["colspan" => 2],
                        A([
                            "id" => "canceldata{$i}",
                            "class" => "upload-cancel",
                            "style" => "visibility:hidden;",
                            "data-target" => "data{$i}",
                        ], "✖ "),
                        INPUT([
                            "type" => "file",
                            "id" => "data{$i}",
                            "name" => "data{$i}[]",
                            "accept" => $accept,
                            "multiple" => true,
                        ]),
                    ),
                    TD(
                        $tl_enabled ? INPUT([
                            "type" => "text",
                            "name" => "url{$i}",
                            "value" => ($i === 0) ? @$_GET['url'] : null,
                        ]) : null
                    ),
                    $specific_fields,
                )
            );
        }

        return $upload_list;
    }

    protected function build_bookmarklets(): HTMLElement
    {
        $limits = get_upload_limits();
        $link = make_link("upload")->asAbsolute();
        $main_page = make_link()->asAbsolute();
        $title = Ctx::$config->get(SetupConfig::TITLE);
        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $delimiter = Url::are_niceurls_enabled() ? '?' : '&amp;';

        $js = 'javascript:(
            function() {
                if(typeof window=="undefined" || !window.location || window.location.href=="about:blank") {
                    window.location = "'. $main_page .'";
                }
                else if(typeof document=="undefined" || !document.body) {
                    window.location = "'. $main_page .'?url="+encodeURIComponent(window.location.href);
                }
                else if(window.location.href.match("\/\/'. $_SERVER["HTTP_HOST"] .'.*")) {
                    alert("You are already at '. $title .'!");
                }
                else {
                    var tags = prompt("Please enter tags", "tagme");
                    if(tags !== "" && tags !== null) {
                        var link = "'. $link . $delimiter .'url="+location.href+"&tags="+tags;
                        var w = window.open(link, "_blank");
                    }
                }
            }
        )();';
        $html1 = P(
            A(["href" => $js], "Upload to $title"),
            emptyHTML(' (Drag & drop onto your bookmarks toolbar, then click when looking at a post)')
        );

        // Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
        $supported_ext = join(" ", DataHandlerExtension::get_all_supported_exts());

        $title = "Booru to " . Ctx::$config->get(SetupConfig::TITLE);
        // CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
        $js = '
            javascript:
            var ste=&quot;'. $link . $delimiter .'url=&quot;;
            var supext=&quot;'.$supported_ext.'&quot;;
            var maxsize=&quot;'.$max_kb.'&quot;;
            var CA=0;
            void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.Url::base()->asAbsolute()."/ext/upload/bookmarklet.js".'&quot;)
        ';
        $html2 = P(
            A(["href" => $js], $title),
            emptyHTML(" (Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))"),
        );

        return emptyHTML($html1, $html2);
    }

    /**
     * @param UploadResult[] $results
     */
    public function display_upload_status(array $results): void
    {
        $page = Ctx::$page;

        /** @var UploadSuccess[] */
        $successes = array_filter($results, fn ($r) => is_a($r, UploadSuccess::class));

        /** @var UploadError[] */
        $errors = array_filter($results, fn ($r) => is_a($r, UploadError::class));

        if (count($errors) > 0) {
            $page->set_title("Upload Status");
            $this->display_navigation();
            foreach ($errors as $error) {
                $page->add_block(new Block($error->name, format_text($error->error)));
                $page->add_cookie("upload_success", "false", time() + 60);
                // $page->add_html_header(SCRIPT("ui_cookie_set(\"upload_success\", \"false\");"));
            }
        } elseif (count($successes) === 0) {
            $page->set_title("No images uploaded");
            $this->display_navigation();
            $page->add_block(new Block("No images uploaded", emptyHTML("Upload attempted, but nothing succeeded and nothing failed?")));
        } elseif (count($successes) === 1) {
            $page->set_redirect(make_link("post/view/{$successes[0]->image_id}"));
            $page->add_http_header("X-Shimmie-Post-ID: " . $successes[0]->image_id);
            $page->add_cookie("upload_success", "true", time() + 60, httponly: false);
        } else {
            $ids = join(",", array_reverse(array_map(fn ($s) => $s->image_id, $successes)));
            $page->set_redirect(search_link(["id={$ids}"]));
            $page->add_cookie("upload_success", "true", time() + 60, httponly: false);
        }
    }

    public function display_edit_page(): void
    {
        $html = emptyHTML();
        $type_map = ["hidden", "half width", "full width", "single column", "dropdown", "single row", "single row (half width)", "info line", "radio row", "checkmark row"];

        foreach (Upload::get_blocks() as $block) {
            $name = $block['name'];
            $color = $block['color'];
            $type = $block['type'];
            $priority = $block['priority'];

            $tags = str_replace(' ', " \n", $block["tags"]);

            $select = SELECT(["name" => "type", "class" => "tc-edit"]);
            foreach ($type_map as $idx => $text) {
                $select->appendChild(OPTION(["value" => (string)$idx, ($type === $idx ? "selected" : "") => true], $text));
            }
            $html->appendChild(DIV(
                ["class" => "tagcategoryblock tc-viewing"],
                SHM_SIMPLE_FORM(
                    make_link("upload/config"),
                    INPUT(["type" => "hidden", "name" => "id", "value" => $block['id']]),
                    TABLE(
                        TR(
                            TD("Name"),
                            TD(
                                SPAN(["class" => "tc-view"], $name),
                                INPUT(["type" => "text", "name" => "name", "class" => "tc-edit", "value" => $name])
                            )
                        ),
                        TR(
                            TD("Color"),
                            TD(
                                SPAN(["class" => "tc-view"], $color),
                                DIV(["class" => "tc_colorswatch tc-view", "style" => "background-color:$color"]),
                                INPUT(["type" => "color", "name" => "color", "class" => "tc-edit", "value" => $color])
                            )
                        ),
                        TR(
                            TD("type"),
                            TD(
                                SPAN(["class" => "tc-view"], $type_map[$type]),
                                $select
                            ),
                        ),
                        TR(
                            TD("Priority"),
                            TD(
                                SPAN(["class" => "tc-view"], $priority),
                                INPUT(["type" => "number", "name" => "priority", "class" => "tc-edit", "value" => $priority]),
                            ),
                        ),
                        TR(
                            TD("tags"),
                            TD(
                                TEXTAREA(
                                    ["type" => "text", "name" => "tags", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5.5", "cols" => "15"],
                                    $tags
                                ),
                            ),
                        ),
                    ),
                    BUTTON([
                        "class" => "tc-view",
                        "type" => "button",
                        "data-tc-mode" => "tc-editing"
                    ], "Edit"),
                    BUTTON([
                        "class" => "tc-edit",
                        "type" => "submit",
                        "name" => "action",
                        "value" => "edit"
                    ], "Submit"),
                    BUTTON([
                        "class" => "tc-delete",
                        "type" => "submit",
                        "name" => "action",
                        "value" => "delete",
                        "data-shm-confirm" => "Are you sure you want to delete $name?"
                    ], "Delete"),
                )
            ));
        }

        // new
        $name = 'example';
        $color = '#EE5542';
        $selectnew = SELECT(["name" => "type"]);
        foreach ($type_map as $idx => $text) {
            $selectnew->appendChild(OPTION(["value" => (string)$idx], $text));
        }
        $html->appendChild(DIV(
            ["class" => "tagcategoryblock"],
            SHM_SIMPLE_FORM(
                make_link("upload/config"),
                TABLE(
                    TR(
                        TD("Name"),
                        TD(
                            INPUT(["type" => "text", "name" => "name", "value" => $name])
                        )
                    ),
                    TR(
                        TD("Color"),
                        TD(
                            INPUT(["type" => "color", "name" => "color", "value" => $color])
                        )
                    ),
                    TR(
                        TD("Type"),
                        TD(
                            $selectnew
                        ),
                    ),
                    TR(
                        TD("Priority"),
                        TD(
                            INPUT(["type" => "number", "name" => "priority", "value" => "0"]),
                        ),
                    ),
                    TR(
                        TD("tags"),
                        TD(
                            TEXTAREA(["type" => "text", "name" => "tags", "class" => "autocomplete_tags", "placeholder" => "tagme", "rows" => "5", "cols" => "15"]),
                        ),
                    ),
                ),
                BUTTON(["class" => "tc_submit", "type" => "submit", "name" => "action", "value" => "new"], "Submit")
            ),
        ));

        Ctx::$page->set_title("Upload configuration");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Editing", $html, "main", 10));
    }

    protected function build_upload_block(): HTMLElement
    {
        $limits = get_upload_limits();

        $accept = $this->get_accept();

        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = $limits['shm_post'];
        $max_total_kb = to_shorthand_int($max_total_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        $form = SHM_FORM(make_link("upload"), multipart: true);
        $form->appendChild(
            emptyHTML(
                INPUT(["id" => "data[]", "name" => "data[]", "size" => "16", "type" => "file", "accept" => $accept, "multiple" => true]),
                INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "class" => "autocomplete_tags", "required" => true]),
                Captcha::get_html(UploadPermission::SKIP_UPLOAD_CAPTCHA),
                INPUT(["type" => "submit", "value" => "Post"]),
            )
        );

        return DIV(
            ["class" => 'mini_upload'],
            $form,
            SMALL(
                "(",
                $max_size > 0 ? "File limit $max_kb" : null,
                $max_size > 0 && $max_total_size > 0 ? " / " : null,
                $max_total_size > 0 ? "Total limit $max_total_kb" : null,
                ")",
            ),
            NOSCRIPT(BR(), A(["href" => make_link("upload")], "Larger Form"))
        );
    }
    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
