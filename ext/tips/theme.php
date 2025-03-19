<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{rawHTML,TABLE,TR,TD,SELECT,OPTION,INPUT,TEXTAREA,DIV,IMG};

/**
 * @phpstan-type Tip array{id: int, image: string, text: string, enable: bool}
 */
class TipsTheme extends Themelet
{
    /**
     * @param string[] $images
     */
    public function manageTips(string $url, array $images): void
    {
        global $page;
        $select = SELECT(
            ["name" => "image"],
            OPTION(
                ["value" => ""],
                "- Select Post -"
            )
        );

        foreach ($images as $image) {
            $select->appendChild(OPTION(
                [
                    "style" => "background-image:url($url$image); background-repeat:no-repeat; padding-left:20px;",
                    "value" => $image,
                ],
                $image
            ));
        }

        $html = SHM_SIMPLE_FORM(make_link("tips/save"));
        $html->appendChild(TABLE(
            TR(
                TD("Enable:"),
                TD(
                    INPUT(
                        ["name" => "enable", "type" => "checkbox", "value" => "Y", "checked" => true]
                    )
                )
            ),
            TR(
                TD("Post:"),
                TD($select)
            ),
            TR(
                TD("Message:"),
                TD(TEXTAREA(["name" => "text"]))
            ),
            TR(
                TD(
                    INPUT(["type" => "submit", "value" => "submit"])
                )
            )
        ));

        $page->set_title("Tips List");
        $this->display_navigation();
        $page->add_block(new Block("Add Tip", $html, "main", 10));
    }

    /**
     * @param Tip $tip
     */
    public function showTip(string $url, array $tip): void
    {
        global $page;

        $html = DIV(
            ["id" => "tips", "class" => "tips-container"],
            (empty($tip['image']) ? null : IMG(["class" => "tips-image", "src" => $url.url_escape($tip['image'])])),
            html_escape($tip['text'])
        );
        $page->add_block(new Block(null, $html, "left", 75));
    }

    /**
     * @param Tip[] $tips
     */
    public function showAll(string $url, array $tips): void
    {
        global $user, $page;

        $html = "<table id='poolsList' class='zebra'>".
            "<thead><tr>".
            "<th>ID</th>".
            "<th>Enabled</th>".
            "<th>Post</th>".
            "<th>Text</th>";

        if ($user->can(TipsPermission::ADMIN)) {
            $html .= "<th>Action</th>";
        }

        $html .= "</tr></thead>";

        foreach ($tips as $tip) {
            $tip_enable = $tip['enable'] ? "Yes" : "No";
            $set_link = "<a href='".make_link("tips/status/".$tip['id'])."'>".$tip_enable."</a>";

            $html .= "<tr>".
                "<td>".$tip['id']."</td>".
                "<td>".$set_link."</td>".
                (
                    empty($tip['image']) ?
                    "<td></td>" :
                    "<td><img alt='' src=".$url.$tip['image']." /></td>"
                ).
                "<td class='left'>".$tip['text']."</td>";

            $del_link = "<a href='".make_link("tips/delete/".$tip['id'])."'>Delete</a>";

            if ($user->can(TipsPermission::ADMIN)) {
                $html .= "<td>".$del_link."</td>";
            }

            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        $page->add_block(new Block("All Tips", rawHTML($html), "main", 20));
    }
}
