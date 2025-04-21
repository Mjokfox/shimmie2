<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, DIV, IMG, INPUT, TABLE, emptyHTML};

class PostSchedulingTheme extends Themelet
{
    public function display_admin_block(): void
    {
        $count = Ctx::$database->get_one("SELECT count(id) FROM scheduled_posts");
        $html = emptyHTML(
            "Current posts in queue: ",
            B($count),
            A(["href" => make_link("post_schedule/list")], " Show")
        );
        Ctx::$page->add_block(new Block("Post scheduling", $html));
    }

    /**
     * @param Image[] $scheduled_posts
     */
    public function display_scheduled_posts(array $scheduled_posts): void
    {
        $html = DIV(["class" => "scheduled-post-list"]);
        if (count($scheduled_posts) <= 0) {
            $html->appendChild("No posts queued");
        }
        foreach ($scheduled_posts as $post) {
            $path = Filesystem::warehouse_path("scheduled_posts", $post->hash);
            $iibbe = send_event(new ImageInfoBoxBuildingEvent($post, $post->get_owner()));
            $html->appendChild(DIV(
                ["class" => "scheduled-post"],
                DIV(
                    ["class" => "schedule-image"],
                    IMG(["src" => make_link($path->str())])
                ),
                TABLE(
                    ["class" => "image_info form infomode-view"],
                    ...$iibbe->get_parts()
                ),
                SHM_SIMPLE_FORM(
                    make_link("post_schedule/remove"),
                    INPUT(["name" => "id", "value" => $post->id, "type" => "hidden"]),
                    SHM_SUBMIT("Delete")
                )
            ));
        }
        Ctx::$page->add_block(new Block(null, $html, 'main'));
    }
}
