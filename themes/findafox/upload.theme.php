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
function customSort(array &$array, array $customOrder): void // custom sorting thingy
{
    usort($array, function ($a, $b) use ($customOrder) {
        $indexA = array_search($a, $customOrder);
        $indexB = array_search($b, $customOrder);
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

/**
 * @param array<mixed> $array
 * @param array<mixed> $customOrder
 */
function customkSort(array &$array, array $customOrder): void // custom key sorting thingy
{
    uksort($array, function ($a, $b) use ($customOrder) {
        $indexA = array_search($a, $customOrder);
        $indexB = array_search($b, $customOrder);
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
    $res = cache_get_or_set("category_table", fn () => $database->get_all("
            SELECT
            itc.display_singular AS category_name,
            t.tag AS tag_name
            FROM image_tag_categories_tags itct
            JOIN image_tag_categories itc ON itct.category_id = itc.id
            JOIN tags t ON itct.tag_id = t.id
            ORDER BY tag_name ASC;
        "), 30);
    /** @var array{string:mixed} $category_tags */
    $category_tags = [];
    $preselect_tags = ["mouth_closed","eyes_open","adult","photo","color","wild"];
    foreach ($res as $row) {
        $category_name = $row['category_name'];
        if (explode(":", $category_name)[0] == "Species") { // implode the multiple species categories into one, tbh it should just be one already
            if (!isset($category_tags["Species"])) {
                $category_tags["Species"] = [];
            }
            $category_tags["Species"][] = $row['tag_name'];
        } else {
            if (!isset($category_tags[$category_name])) {
                $category_tags[$category_name] = [];
            }

            $category_tags[$category_name][] = $row['tag_name'];
        }
    }
    ksort($category_tags);
    if (array_key_exists("Meta", $category_tags)) { // move Meta to the back
        $value = $category_tags["Meta"];
        unset($category_tags["Meta"]);
        $category_tags["Meta"] = $value;
    }
    $html_input_array = [];
    $tags_input = emptyHTML();
    if (array_key_exists("Meta", $category_tags)) { //meta specific ordering
        $tags = $category_tags["Meta"];
        $metas = ["single", "multiple","multiple_species"];
        customSort($tags, $metas);
        $tempHtml = emptyHTML();
        foreach ($tags as $tag) {
            if (in_array($tag, $metas)) {
                $tempHtml->appendChild(make_input_label($suffix, $tag, "Metas", "radio", "checkboxRadio(this);"));
            }
        }
        // $tags_input->appendChild(
        $html_input_array["Amount"] =
            DIV(
                ["class" => "grid-cell-wide"],
                DIV(["class" => "grid-cell-label"], "Amount"),
                DIV(["class" => "grid-cell-separator"]),
                DIV(["class" => "grid-cell-content dir-row"], $tempHtml, ),
            );
        // );
        $category_tags["Meta"] = array_diff($category_tags["Meta"], $metas);
        arsort($tags);
    }

    if (array_key_exists("Species", $category_tags)) { //species specific ordering
        $tags = $category_tags["Species"];
        $common_species = ['red_fox', 'arctic_fox', 'fennec_fox','gray_fox'];
        customSort($tags, $common_species);
        $tempHtml = emptyHTML();
        $dropdownHtml = emptyHTML();
        $dropdownHtml->appendChild(OPTION(["value" => ""], "less common species"));
        foreach ($tags as $tag) {
            if (in_array($tag, $common_species)) {
                $tempHtml->appendChild(make_input_label($suffix, $tag, "Species", "checkbox", "checkboxRadio(this);presettags(this);"));
            } else {
                $dropdownHtml->appendChild(
                    OPTION(["value" => $tag, "onClick" => "presettags(this);"], $tag)
                );
            }
        }

        // $tags_input->appendChild(
        $html_input_array["Species"] =
            DIV(
                ["class" => "grid-cell-wide"],
                DIV(["class" => "grid-cell-label"], "Species"),
                DIV(["class" => "grid-cell-separator"]),
                DIV(
                    ["class" => "grid-cell-content dir-row"],
                    $tempHtml,
                    SELECT(
                        ["id" => "tagsDropdown_{$suffix}", "style" => "width:auto","onclick" => "updateTags(this);"],
                        $dropdownHtml
                    ),
                ),
            );
        // );
        unset($category_tags["Species"]);
    }

    if (array_key_exists("Body:Face", $category_tags)) { //face specific ordering
        $tags = $category_tags["Body:Face"];
        arsort($tags);
        $tempHtmls = [emptyHTML(),emptyHTML(),emptyHTML(),emptyHTML()];
        $lables = ["Facial features","Eye color","Nose color","Misc facial"];
        $counts = [0,0,0,0,0];
        foreach ($tags as $tag) {
            $tagarray = explode("_", $tag);
            if (in_array("eyes", $tagarray)) {
                if (array_search("eyes", $tagarray) == 0) {
                    $tempHtmls[0]->appendChild(make_input_label($suffix, $tag, "EyesMouth1", "checkbox", "", "", in_array($tag, $preselect_tags)));
                    $counts[0]++;
                } else {
                    $tempHtmls[1]->appendChild(make_input_label($suffix, $tag, "Eyes", "checkbox"));
                    $counts[1]++;
                }
                // } elseif (in_array("muzzle", $tagarray)) {
                //     $tempHtmls[3]->appendChild(make_input_label($suffix, $tag, "Muzzle", "checkbox", "", ""));
                //     $counts[3]++;
            } elseif (in_array("mouth", $tagarray)) {
                $tempHtmls[0]->appendChild(make_input_label($suffix, $tag, "EyesMouth2", "checkbox", "", "", in_array($tag, $preselect_tags)));
                $counts[0]++;
            } elseif (in_array("nose", $tagarray)) {
                $tempHtmls[2]->appendChild(make_input_label($suffix, $tag, "Nose", "checkbox"));
                $counts[2]++;
            } else {
                $tempHtmls[3]->appendChild(make_input_label($suffix, $tag, "FaceMisc", "checkbox"));
                $counts[3]++;
            }


        }
        $i = 0;
        foreach ($tempHtmls as $tempHtml) {
            $rows = ceil($counts[$i] / 2);
            $rows4 = max(4, $rows);
            $html_input_array[$lables[$i]] =
                DIV(
                    ["class" => "grid-cell"],
                    DIV(["class" => "grid-cell-label"], $lables[$i]),
                    DIV(["class" => "grid-cell-separator"]),
                    DIV(["class" => "grid-cell-content", "style" => "--rows: $rows4;--tworows: $rows"], $tempHtml, )
                );
            $i++;
        }
        unset($category_tags["Body:Face"]);
    }

    if (array_key_exists("Body:Fur", $category_tags)) { //fur specific ordering
        $tags = $category_tags["Body:Fur"];
        arsort($tags);
        $fur_order = ['red_fur', 'white_fur', 'gray_fur','tan_fur','black_fur','brown_fur'];
        customSort($tags, $fur_order);
        $tempHtmls = [null,emptyHTML(),emptyHTML()];
        $lables = ["Age","Fur color","Coat"];
        $counts = [0,0,0,0];
        if (array_key_exists("Body:Age", $category_tags)) { //fur specific ordering
            $tempHtmls[0] = emptyHTML();
            foreach ($category_tags["Body:Age"] as $taga) {
                $tempHtmls[0]->appendChild(make_input_label($suffix, $taga, "Age", "checkbox", "", "", in_array($taga, $preselect_tags)));
                $counts[0]++;
            }
            unset($category_tags["Body:Age"]);
        }
        foreach ($tags as $tag) {
            $tagarray = explode("_", $tag);
            if (in_array("fur", $tagarray)) {
                $tempHtmls[1]->appendChild(make_input_label($suffix, $tag, "FurColor", "checkbox"));
                $counts[1]++;
                // } elseif (in_array("tail", $tagarray)) {
                //     $tempHtmls[2]->appendChild(make_input_label($suffix, $tag, "TailTip", "checkbox"));
                //     $counts[2]++;
            } else {
                $tempHtmls[2]->appendChild(make_input_label($suffix, $tag, "Furmisc", "checkbox"));
                $counts[2]++;
            }
        }
        $i = 0;
        foreach ($tempHtmls as $tempHtml) {
            if ($tempHtml != null) {
                $rows = ceil($counts[$i] / 2);
                $rows4 = max(4, $rows);
                $html_input_array[$lables[$i]] =
                    DIV(
                        ["class" => "grid-cell"],
                        DIV(["class" => "grid-cell-label"], $lables[$i]),
                        DIV(["class" => "grid-cell-separator"]),
                        DIV(["class" => "grid-cell-content", "style" => "--rows: $rows4;--tworows: $rows"], $tempHtml, )
                    );
            }
            $i++;
        }
        unset($category_tags["Body:Fur"]);
    }
    if (count($category_tags) > 0) {
        $input_array = [];
        $category_array = [];
        $count_array = [];
        $radio_categories = ["Time"]; // still some hardcoded bits tho...
        $hidden_categories = ["Genus", "Name","Type"];
        $wide_categories = ["Meta","Activity"];
        $sort_categories = ["SP:Red fox specific" => '/(muzzle|tip)/'];
        foreach (array_keys($category_tags) as $category_tag) {
            if (in_array($category_tag, $hidden_categories)) {
                continue;
            }
            $string_array = explode(":", $category_tag);
            if (count($string_array) > 1) {
                $category_upper_name = $string_array[0];
                $category_lower_name = $string_array[1];
            } else {
                $category_upper_name = $category_tag;
                $category_lower_name = "Meta";
            }
            if (!array_key_exists($category_upper_name, $input_array)) {
                $input_array[$category_upper_name] = [];
                $category_array[$category_upper_name] = true;
            }
            if (array_key_exists($category_tag, $sort_categories)) {
                usort($category_tags[$category_tag], function ($a, $b) use ($sort_categories, $category_tag) {
                    preg_match($sort_categories[$category_tag], $a, $matchA);
                    preg_match($sort_categories[$category_tag], $b, $matchB);
                    $groupA = $matchA[1] ?? $a;
                    $groupB = $matchB[1] ?? $b;
                    if ($groupA !== $groupB) {
                        return strcmp($groupA, $groupB);
                    }
                    return strnatcmp($a, $b);
                });
            }
            $count_array[$category_upper_name][$category_lower_name] = count($category_tags[$category_tag]);
            $input_array[$category_upper_name][$category_lower_name] = emptyHTML();
            $type = in_array($category_lower_name, $radio_categories) ? "radio" : "checkbox";
            $stop = $count_array[$category_upper_name][$category_lower_name] / (in_array($category_lower_name, $wide_categories) ? 4 : 2);
            $i = 0;
            foreach ($category_tags[$category_tag] as $tag) {
                $input_array[$category_upper_name][$category_lower_name]->appendChild(make_input_label($suffix, $tag, $category_lower_name, $type, "", $i < $stop && $i % 4 == 3 ? "label-margin" : "", in_array($tag, $preselect_tags)));
                $i++;
            }
        }
        foreach (array_keys($category_array) as $category) {
            foreach (array_keys($input_array[$category]) as $lower_category) {
                $rows = max(4, ceil($count_array[$category][$lower_category] / (in_array($lower_category, $wide_categories) ? 4 : 2)));
                $tworows = ceil($count_array[$category][$lower_category] / 2);
                $html_input_array[$lower_category] =
                    DIV(
                        ["class" => in_array($lower_category, $wide_categories) ? "grid-cell-wide" : "grid-cell"],
                        DIV(["class" => "grid-cell-label"], $lower_category),
                        DIV(["class" => "grid-cell-separator"]),
                        DIV(["class" => "grid-cell-content", "style" => "--rows: $rows;--tworows: $tworows"], $input_array[$category][$lower_category], ),
                    );
            }
        }
    }
    $upload_order = $config->get_string("upload_order");
    $category_sort = array_map('trim', explode(",", $upload_order));
    customkciSort($html_input_array, $category_sort);
    foreach ($html_input_array as $whatever) {
        $tags_input->appendChild($whatever);
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
