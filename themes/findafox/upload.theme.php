<?php

declare(strict_types=1);

namespace Shimmie2;


use function MicroHTML\{joinHTML, emptyHTML, DIV, BUTTON, A, TEXTAREA, TABLE, TR, TH, TD, INPUT, LABEL, BR, SELECT, OPTION};

use MicroHTML\HTMLElement;

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
}

function make_input_label($suffix,$tag,$id,$type="radio",$br=null,$onclicks="",$class="",$selected=false): HTMLElement
{
    return LABEL(INPUT(
        array_merge(
            [
                "type" => "{$type}",
                "name" => "{$id}_{$suffix}",
                "id" => "tagsInput_{$suffix}",
                "class" => "tagsInput_{$suffix} {$class}",
                "value" => $tag,
                "onClick" => "updateTags(this); {$onclicks}"
            ],
            $selected ? ["checked" => "true"] : []
        ),),
                 "{$tag} ",$br ? BR() : "")
    ;
}

function customSort(array &$array, array $customOrder): void { // custom sorting thingy
    usort($array, function($a, $b) use ($customOrder) {
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

class CustomPostTagsTheme extends PostTagsTheme
{
    public function get_upload_specific_html(string $suffix): HTMLElement
    {
        global $database,$config;
        $res = $database->get_all("
        SELECT
        itc.display_singular AS category_name,
        t.tag AS tag_name
        FROM image_tag_categories_tags itct
        JOIN image_tag_categories itc ON itct.category_id = itc.id
        JOIN tags t ON itct.tag_id = t.id;
        ");
        $category_tags = [];
        $preselect_tags = ["mouth_closed","eyes_open","adult","photo","color","wild"];
        foreach ($res as $row) {
            $category_name = $row['category_name'];
            if (explode(":",$category_name)[0] == "Species"){ // implode the multiple species categories into one, tbh it should just be one already
                if (!isset($category_tags["Species"])) {
                    $category_tags["Species"] = [];
                }
                $category_tags["Species"][] = $row['tag_name'];
            } else{
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
        $tags_input = emptyHTML();
        $tags_input->appendChild(
            TR(["class" => "header"],
                TH(["class" => "right min_width_td"],
                    // ["style" => "width:20%"],
                    "Category"
                ),
                TH(["class" => "max_width_td"],
                    // ["style" => "width:80%"],
                    "Tag"
                ),
            ),
        );
        // hardcoded ordering
        if (array_key_exists("Meta",$category_tags)){ //meta specific ordering
            $tags = $category_tags["Meta"];
            arsort($tags);
            $metas = ["single", "multiple","multiple_species"];
            $tempHtml = emptyHTML();
            foreach($tags as $tag){
                if (in_array($tag,$metas)){
                    $tempHtml->appendChild(make_input_label($suffix,$tag,"Metas","radio",false,"checkboxRadio(this);"));
                }
            }
            $tags_input->appendChild(
                TR(
                    TD(["class" => "right min_width_td"],"Amount"),
                    TD(["class" => "max_width_td"],$tempHtml,),
                )
            );
            $category_tags["Meta"] = array_diff($category_tags["Meta"],$metas);
        }
        if (array_key_exists("Species",$category_tags)){ //species specific ordering
            $tags = $category_tags["Species"];
            $common_species = ['red_fox', 'arctic_fox', 'fennec_fox','gray_fox'];
            customSort($tags,$common_species);
            $tempHtml = emptyHTML();
            $dropdownHtml = emptyHTML();
            $dropdownHtml->appendChild(OPTION(["value" => ""],"less common species"));
            foreach($tags as $tag){
                if (in_array($tag,$common_species)){
                    $tempHtml->appendChild(make_input_label($suffix,$tag,"Species","checkbox",false,"checkboxRadio(this);presettags(this);"));
                }
                else{
                    $dropdownHtml->appendChild(
                        OPTION(["value" => $tag, "onClick" => "presettags(this);"],$tag)
                    );
                }
            }

            $tags_input->appendChild(
                TR(
                    TD(["class" => "right min_width_td"],
                        "Species"
                    ),
                    TD(["class" => "max_width_td"],
                        $tempHtml,
                        SELECT(
                            ["id" => "tagsDropdown_{$suffix}", "style" => "width:auto","onclick" => "updateTags(this);"],
                               $dropdownHtml
                        )
                    ),
                ),
            );
            unset($category_tags["Species"]);
        }

        if (array_key_exists("Body:Face",$category_tags)){ //face specific ordering
            $tags = $category_tags["Body:Face"];
            arsort($tags);
            $tempHtml1 = emptyHTML();
            $tempHtml2 = emptyHTML();
            $tempHtml3 = emptyHTML();
            $tempHtml4 = emptyHTML();
            $tempHtml5 = emptyHTML();
            foreach($tags as $tag){
                $tagarray = explode("_",$tag);
                if (in_array("eyes",$tagarray)){
                    if(array_search("eyes",$tagarray) == 0){
                        $tempHtml1->appendChild(make_input_label($suffix,$tag,"EyesMouth1","checkbox",true,"","",in_array($tag,$preselect_tags)));
                    } else{
                        $tempHtml2->appendChild(make_input_label($suffix,$tag,"Eyes","checkbox",true));
                    }
                }
                elseif (in_array("muzzle",$tagarray)){
                    $tempHtml4->appendChild(make_input_label($suffix,$tag,"Muzzle","checkbox",true,"","disabledOnStartup"));
                }
                elseif (in_array("mouth",$tagarray)){
                    $tempHtml1->appendChild(make_input_label($suffix,$tag,"EyesMouth2","checkbox",true,"","",in_array($tag,$preselect_tags)));
                }
                elseif (in_array("nose",$tagarray)){
                    $tempHtml3->appendChild(make_input_label($suffix,$tag,"Nose","checkbox",true));
                }
                else {
                    $tempHtml5->appendChild(make_input_label($suffix,$tag,"FaceMisc","checkbox",true));
                }


            }
            $tags_input->appendChild(
                TR(TD(["class" => "right min_width_td"],"Facial features"),
                    TD(["class" => "max_width_td"],
                       DIV(["class" => "table_container","style" => "width: 100%;"],
                           DIV(["class" => "header_row"],
                               DIV("open|closed"),
                               DIV("Eye color"),
                               DIV("Nose color"),
                               DIV("muzzle marking"),
                               DIV("misc"),
                           ),
                           DIV(["class" => "cell_row"],
                               DIV($tempHtml1),
                               DIV($tempHtml2),
                               DIV($tempHtml3),
                               DIV($tempHtml4),
                               DIV($tempHtml5),
                           ))),
                )
            );
            unset($category_tags["Body:Face"]);
        }

        if (array_key_exists("Body:Fur",$category_tags)){ //fur specific ordering
            $tags = $category_tags["Body:Fur"];
            arsort($tags);
            $fur_order = ['red_fur', 'white_fur', 'gray_fur','tan_fur','black_fur'];
            customSort($tags,$fur_order);
            $tempHtml1 = null;
            if (array_key_exists("Body:Age",$category_tags)){ //fur specific ordering
                $tempHtml1 = emptyHTML();
                foreach($category_tags["Body:Age"] as $taga){
                    $tempHtml1->appendChild(make_input_label($suffix,$taga,"Age","checkbox",true,"","",in_array($taga,$preselect_tags)));
                }
                unset($category_tags["Body:Age"]);
            }
            $tempHtml2 = emptyHTML();
            $tempHtml3 = emptyHTML();
            $tempHtml4 = emptyHTML();
            foreach($tags as $tag){
                $tagarray = explode("_",$tag);
                if (in_array("fur",$tagarray)){
                    $tempHtml2->appendChild(make_input_label($suffix,$tag,"FurColor","checkbox",true));
                }
                elseif (in_array("tail",$tagarray)){
                    $tempHtml3->appendChild(make_input_label($suffix,$tag,"TailTip","checkbox",true));
                }
                else {
                    $tempHtml4->appendChild(make_input_label($suffix,$tag,"Furmisc","checkbox",true));
                }
            }
            $tags_input->appendChild(
                TR(TD(["class" => "right min_width_td"],"Body features"),
                    TD(["class" => "max_width_td"],
                       DIV(["class" => "table_container","style" => "width: 100%;"],
                           DIV(["class" => "header_row"],
                               $tempHtml1 ? DIV("Age") : "",
                               DIV("Fur color"),
                               DIV("Tail tip"),
                               DIV("misc"),
                           ),
                           DIV(["class" => "cell_row"],
                               $tempHtml1 ? DIV($tempHtml1) : "",
                               DIV($tempHtml2),
                               DIV($tempHtml3),
                               DIV($tempHtml4),
                           ))),
                )
            );
            unset($category_tags["Body:Fur"]);
        }

        // dynamic ordering
        if(count($category_tags) > 0){
            $input_array = [];
            $category_array = [];
            $radio_categories = ["Time"]; // still some hardcoded bits tho...
            $hidden_categories = ["Genus", "Name","Type"];
            foreach (array_keys($category_tags) as $category_tag) {
                if (in_array($category_tag,$hidden_categories)) continue;
                $string_array = explode(":",$category_tag);
                if (count($string_array) > 1){
                    $category_upper_name = $string_array[0];
                    $category_lower_name = $string_array[1];
                } else {
                    $category_upper_name = $category_tag;
                    $category_lower_name = "Misc";
                }

                if (!array_key_exists($category_upper_name,$input_array)){
                    $input_array[$category_upper_name] = [];
                    $category_array[$category_upper_name] = true;
                }
                $input_array[$category_upper_name][$category_lower_name] = emptyHTML();
                $type = in_array($category_lower_name,$radio_categories) ? "radio" : "checkbox";
                foreach($category_tags[$category_tag] as $tag){
                    $input_array[$category_upper_name][$category_lower_name]->appendChild(make_input_label($suffix,$tag,$category_lower_name,$type,true,"","",in_array($tag,$preselect_tags)));
                }
            }
            foreach(array_keys($category_array) as $category){
                $headerHtml = emptyHTML();
                $cellHtml = emptyHTML();
                $realcell = emptyHTML();
                foreach(array_keys($input_array[$category]) as $lower_category){
                    $headerHtml->appendChild(
                        TH(["class" => "left"],$lower_category)
                    );
                    $cellHtml->appendChild(
                        TD(DIV(["class" => "grid-container"],$input_array[$category][$lower_category]))
                    );
                }
                $tags_input->appendChild(
                    TR(
                        TD(["class" => "right min_width_td"],
                            $category
                        ),
                        TD(["class" => "max_width_td"],
                            TABLE(["style" => "width: 100%;","class" => "table_container"],
                                TR(["class" => "header_row"],
                                    $headerHtml
                                ),
                                TR(["class" => "top"],
                                    $cellHtml
                                ),
                            ),
                        ),
                    ),
                );
            }
        }
        $upload_count = $config->get_int(UploadConfig::COUNT) - 1;
        return TR(TD(["colspan" => "2"],            // love me wayyyyyy too lov return statements lmao
                     TABLE(["style" => "width: 100%;", "class" => "tableSpacing"],$tags_input),
                     DIV(
                         TEXTAREA(["type" => "text","name" => "tags{$suffix}","id" => "tags{$suffix}","class" => "autocomplete_tags","readonly" => true,"rows" => "2", "cols" => "15","value" => ($suffix == 0) ? @$_GET['tags'] : null,]),
                         DIV(["style" => "display:flex"],
                            INPUT(["type" => "button","id" => "Copy_{$suffix}","onclick" => "copyTagsTo(this,document.getElementById('CopyNumber_{$suffix}'))","value" => "Copy this input to:","style" => "width:auto; padding-left:10px;padding-right:10px; "]),
                            INPUT(["type" => "number","id" => "CopyNumber_{$suffix}","value" => "{$suffix}","min" => "0","max" => "{$upload_count}","style" => "width:auto"]),
                            INPUT(["type" => "button","id" => "tagsClear_{$suffix}","onclick" => "clearInputs(this)","value" => "Clear input","style" => "width:20%; margin-left: auto;"]),
                         ),
                     )
        ));
    }
}
