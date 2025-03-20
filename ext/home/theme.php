<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{BODY, emptyHTML, TITLE, META, DIV, INPUT};
use function MicroHTML\A;
use function MicroHTML\H1;
use function MicroHTML\IMG;
use function MicroHTML\SMALL;
use function MicroHTML\SPAN;
use function MicroHTML\joinHTML;

class HomeTheme extends Themelet
{
    public function display_page(Page $page, string $sitename, HTMLElement $body): void
    {
        $page->set_mode(PageMode::DATA);
        $page->add_auto_html_headers();

        $page->set_data((string)$page->html_html(
            emptyHTML(
                TITLE($sitename),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                $page->get_all_html_headers(),
            ),
            $body
        ));
    }

    public function build_body(
        string $sitename,
        HTMLElement $main_links,
        ?string $main_text,
        ?string $contact_link,
        int $post_count,
    ): HTMLElement {
        global $page;
        $page->set_layout("front-page");

        return BODY(
            $page->body_attrs(),
            DIV(
                ["id" => "front-page"],
                $this->build_title($sitename),
                $this->build_links($main_links),
                $this->build_search(),
                $this->build_message($main_text),
                $this->build_counter($post_count),
                $this->build_footer($contact_link, $post_count),
            ),
        );
    }

    protected function build_title(string $sitename): HTMLElement
    {
        return H1(A(["href" => make_link()], SPAN($sitename)));
    }

    protected function build_links(HTMLElement $links): ?HTMLElement
    {
        if (empty((string)$links)) {
            return null;
        }
        return DIV(["class" => "space", "id" => "links"], $links);
    }

    protected function build_search(): HTMLElement
    {
        global $config, $user;
        $search_html = emptyHTML(DIV(
            ["class" => "space", "id" => "search"],
            SHM_FORM(
                action: search_link(),
                method: "GET",
                children: [
                    INPUT(["name" => "search", "size" => "30", "type" => "search", "placeholder" => "tag search", "class" => "autocomplete_tags", "autofocus" => true]),
                    " ",
                    SHM_SUBMIT("Search")
                ]
            )
        ));
        if (ReverseImageInfo::is_enabled() && $config->get_bool(ReverseImageConfig::SEARCH_ENABLE) && $user->get_config()->get_bool(ReverseImageUserConfig::USER_SEARCH_ENABLE)) {
            $search_html->appendChild(DIV(
                ["class" => "space", "id" => "text-search"],
                SHM_FORM(
                    action: make_link("post/search"),
                    method: "GET",
                    children: [
                        INPUT(["name" => "search", "size" => "30", "type" => "search", "placeholder" => "text search", "class" => "autocomplete_tags"]),
                        " ",
                        SHM_SUBMIT("Search")
                    ]
                )
            ));
        }
        return $search_html;
    }

    protected function build_message(?string $main_text): ?HTMLElement
    {
        if (empty($main_text)) {
            return null;
        }
        return DIV(["class" => "space", "id" => "message"], $main_text);
    }

    protected function build_counter(int $post_count): ?HTMLElement
    {
        global $config;

        $counter_dir = $config->get_string(HomeConfig::COUNTER, 'default');
        if ($counter_dir === 'none' || $counter_dir === 'text-only') {
            return null;
        }

        $base_href = Url::base();
        $counter_digits = [];
        foreach (str_split((string)$post_count) as $cur) {
            $counter_digits[] = IMG([
                'class' => 'counter-img',
                'alt' => $cur,
                'src' => "$base_href/ext/home/counters/$counter_dir/$cur.gif"
            ]);
        }
        return DIV(["class" => "space", "id" => "counter"], joinHTML('', $counter_digits));
    }

    protected function build_footer(?string $contact_link, int $post_count): HTMLElement
    {
        $num_comma = number_format($post_count);
        return DIV(
            ["class" => "space", "id" => "foot"],
            SMALL(SMALL(
                empty($contact_link)
                    ? null
                    : emptyHTML(A(["href" => $contact_link], "Contact"), " - "),
                " Serving $num_comma posts - ",
                " Running ",
                A(["href" => "https://code.shishnet.org/shimmie2/"], "Shimmie2")
            ))
        );
    }
}
