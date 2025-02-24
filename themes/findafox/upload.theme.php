<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{ emptyHTML, DIV, SPAN, TEXTAREA, TABLE, TR, TH, TD, INPUT, LABEL, BR,B, SELECT, OPTION};

class CustomUploadTheme extends UploadTheme
{
    public function display_block(Page $page): void
    {
        // this theme links to /upload
        // $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
    }

    public function display_page(Page $page): void
    {
        $page->set_layout("no-left");
        parent::display_page($page);
    }

    protected function build_upload_list(): HTMLElement
    {
        global $config;
        $upload_list = emptyHTML();
        $upload_count = $config->get_int(UploadConfig::COUNT);
        $preview_enabled = $config->get_bool(UploadConfig::PREVIEW);
        $split_view = $config->get_bool(UploadConfig::SPLITVIEW);
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $headers = emptyHTML();
        $uhbe = send_event(new UploadHeaderBuildingEvent());
        foreach ($uhbe->get_parts() as $part) {
            $headers->appendChild(
                TH("Post $part")
            );
        }

        $upload_list->appendChild(
            TR(
                ["class" => "header"],
                TH(["colspan" => 2], "Select File"),
                TH($tl_enabled ? "or URL" : null),
                // $headers,
            )
        );
        $colors = ["F00","F80","FF0","8F0","0F0","0F8","0FF","08F","00F","80F","F0F","F08"];
        $alpha = "2";
        for ($i = 0; $i < $upload_count; $i++) {
            $specific_fields = emptyHTML();
            $usfbe = send_event(new UploadSpecificBuildingEvent((string)$i));
            foreach ($usfbe->get_parts() as $part) {
                $specific_fields->appendChild($part);
            }
            $color = "#".$colors[$i % 11].$alpha;

            $upload_list->appendChild(
                TR(
                    ["id" => "rowdata{$i}","style" => "background-color:".$color],
                    TD(
                        ["colspan" => 2, "style" => "white-space: nowrap;"],
                        SPAN("{$i} "),
                        DIV([
                            "id" => "canceldata{$i}",
                            "class" => "uploadCancelButton",
                            "onclick" => "document.getElementById('data{$i}').value='';updateTracker();",
                        ], "✖"),
                        INPUT([
                            "type" => "file",
                            "id" => "data{$i}",
                            "name" => "data{$i}[]",
                            "accept" => $accept,
                            "multiple" => false,
                            "style" => "display:none",
                        ]),
                        INPUT([
                           "type" => "button",
                           "value" => "Browse...",
                           "id" => "browsedata{$i}",
                           "onclick" => "document.getElementById('data{$i}').click();" ,
                       ]),
                    ),
                    TD(
                        $tl_enabled ? INPUT([
                            "type" => "text",
                            "class" => "url-input",
                            "id" => "urldata{$i}",
                            "name" => "url{$i}",
                            "value" => ($i == 0) ? @$_GET['url'] : null,
                        ]) : null
                    ),
                    TD(
                        ["style" => "text-align:center"],
                        DIV([
                            "id" => "showinputdata{$i}",
                            "class" => "showInputButton",
                            "onclick" => "input_button_handler($i,this,'$color');",
                        ], "Show Input"),
                    ),
                    $preview_enabled ? TD(
                        ["style" => "text-align:center"],
                        DIV([
                           "id" => "showpreviewdata{$i}",
                           "class" => "showPreviewButton",
                           "onclick" => "preview_button_handler($i,this,'$color');",
                       ], "Preview"),
                    ) : "",
                ),
                TR(
                    ["style" => "background-color:".$color],
                    TD(
                        ["colspan" => "100%"],
                        DIV(
                            [
                                "id" => "inputdivdata{$i}",
                                "style" => "display: none",
                                "class" => $split_view ? "upload-split-view" : "",
                            ],
                            TABLE(
                                ["id" => "small_upload_form", "class" => "form","style" => "width:100%"],
                                TR(["class" => "header"], $headers),
                                TR(
                                    ["class" => "header"],
                                    $specific_fields,
                                ),
                            ),
                            get_categories_html((string)$i),
                        ),
                    ),
                )
            );
        }

        return $upload_list;
    }
}

function make_input_label(int|string $suffix, string $tag, int|string $id, string $type = "radio", string $onclicks = "", string $class = "", bool $selected = false): HTMLElement
{
    return LABEL(
        INPUT(
            array_merge(
                [
                    "type" => "{$type}",
                    "var" => "{$id}_{$suffix}",
                    "id" => "tagsInput_{$suffix}",
                    "class" => "tagsInput_{$suffix} {$class}",
                    "value" => $tag,
                    "onClick" => "updateTags(this); {$onclicks}"
                ],
                $selected ? ["checked" => "true"] : []
            ),
        ),
        "{$tag} "
    )
    ;
}

/**
 * @param array<mixed> $array
 * @param array<mixed> $customOrder
 */
function customkciSort(array &$array, array $customOrder): void // custom key case insensitive sorting thingy
{
    $customOrderLower = array_map('strtolower', $customOrder);

    uksort($array, function ($a, $b) use ($customOrderLower) {
        $indexA = array_search(strtolower($a), $customOrderLower);
        $indexB = array_search(strtolower($b), $customOrderLower);

        if ($indexA !== false && $indexB !== false) {
            return $indexA <=> $indexB;
        }
        if ($indexA !== false) {
            return -1;
        }
        if ($indexB !== false) {
            return 1;
        }
        return 0;
    });
}

function get_categories_html(string $suffix): HTMLElement
{
    global $database,$config,$cache;
    /** @var array{string:string} $types */
    $types = cache_get_or_set("ct_type_table", fn () => $database->get_pairs("
        SELECT lower_group, upload_page_type
        FROM image_tag_categories itc
        "), 1);
    $res = cache_get_or_set("category_table", fn () => $database->get_all("
            SELECT
            itc.lower_group AS group,
            t.tag AS tag_name
            FROM image_tag_categories_tags itct
            JOIN image_tag_categories itc ON itct.category_id = itc.id
            JOIN tags t ON itct.tag_id = t.id
            ORDER BY itc.upload_page_priority DESC, itct.id ASC;
        "), 1);
    /** @var array{string:mixed} $tc_dict */
    $tc_dict = [];
    $preselect_tags = ["mouth_closed","eyes_open","adult","photo","color","wild"];
    foreach ($res as $row) {
        $group = $row['group'];

        if (!isset($tc_dict[$group])) {
            $tc_dict[$group] = [];
        }
        $tc_dict[$group][] = $row['tag_name'];
    }

    $tags_input = emptyHTML();

    $input_array = [];
    $count_array = [];

    $type_table = [1 => ["cols" => 2, "class" => "grid-cell"],
    2 => ["cols" => 4, "class" => "grid-cell cell-wide"],
    3 => ["cols" => 1, "class" => "grid-cell cell-thin"],
    4 => ["cols" => 4, "class" => "grid-cell cell-wide"]];
    foreach (array_keys($tc_dict) as $group) {
        $type = $types[$group];
        if (!$type) {
            continue;
        }

        if (!array_key_exists($group, $input_array)) {
            $input_array[$group] = emptyHTML();
        }

        $count_array[$group] = count($tc_dict[$group]);

        $stop = $count_array[$group] / $type_table[$type]["cols"];

        if ($type == 4) {
            $i = 0;
            $dropdownHtml = emptyHTML();
            foreach ($tc_dict[$group] as $tag) {
                if ($i++ < 4) {
                    $input_array[$group]->appendChild(make_input_label($suffix, $tag, $group, $type == 4 ? "radio" : "checkbox", "", $i < $stop && $i % 4 == 3 ? "label-margin" : "", in_array($tag, $preselect_tags)));
                } else {
                    $dropdownHtml->appendChild(
                        OPTION(["value" => $tag, "onClick" => "presettags(this);"], $tag)
                    );
                }
            }
            if ($i > 4){
                $input_array[$group]->appendChild(
                    SELECT(
                        ["id" => "tagsDropdown_{$suffix}", "style" => "width:auto","onclick" => "updateTags(this);"],
                        OPTION(["value" => ""], "More..."),
                        $dropdownHtml
                    )
                );
            }
        } else {
            $i = 0;
            foreach ($tc_dict[$group] as $tag) {
                $input_array[$group]->appendChild(make_input_label($suffix, $tag, $group, $type == 4 ? "radio" : "checkbox", "", $i < $stop && $i % 4 == 3 ? "label-margin" : "", in_array($tag, $preselect_tags)));
                $i++;
            }
        }
    }

    foreach (array_keys($input_array) as $group) {
        $type = $types[$group];
        $rows = max(4, ceil($count_array[$group] / $type_table[$type]["cols"]));
        $tworows = ceil($count_array[$group] / 2);
        $tags_input->appendChild(
            DIV(
                ["class" => $type_table[$type]["class"]],
               
                DIV(["class" => "grid-cell-separator"], DIV(["class" => "grid-cell-label"], $group),),
                DIV(["class" => "grid-cell-content" . ($type == 4 ? " dir-row" : ""), "style" => "--rows: $rows;--tworows: $tworows"], $input_array[$group], ),
            )
        );
    }
    $upload_count = $config->get_int(UploadConfig::COUNT) - 1;
    $output = emptyHTML();
    $output->appendChild(DIV(
        ["class" => "dont-offset"],
        TEXTAREA(["name" => "faketags{$suffix}","id" => "usertags_{$suffix}","class" => "autocomplete_tags user-input-tags","placeholder" => "Custom tags","rows" => "2", "cols" => "15",]),
    ));
    $output->appendChild(DIV(["class" => "upload-tags-grid"], $tags_input));
    $output->appendChild(DIV(
        ["class" => "dont-offset"],
        B("Tags from this panel:"),
        TEXTAREA(["name" => "faketags{$suffix}","id" => "faketags_{$suffix}","placeholder" => "Tags from the input panel above","readonly" => true,"rows" => "1", "cols" => "15","style" => "cursor:"]),
        INPUT(["type" => "text","name" => "tags{$suffix}","id" => "tags{$suffix}","readonly" => true, "style" => "display: none;"]),
        DIV(
            ["style" => "display:flex"],
            INPUT(["type" => "button","id" => "Copy_{$suffix}","onclick" => "copyTagsTo(this,document.getElementById('CopyNumber_{$suffix}'))","value" => "Copy this input to:","style" => "width:auto; padding-left:10px;padding-right:10px; "]),
            INPUT(["type" => "number","id" => "CopyNumber_{$suffix}","value" => "{$suffix}","min" => "0","max" => "{$upload_count}","style" => "width:auto"]),
            INPUT(["type" => "button","id" => "tagsClear_{$suffix}","onclick" => "clearInputs(this)","value" => "Clear input","style" => "width:20%; margin-left: auto;"]),
        ),
    ));
    return $output;
}

class CustomPostTagsTheme extends PostTagsTheme
{
    public function get_upload_specific_html(string $suffix): HTMLElement
    {
        return emptyHTML();
    }
}
