<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{META,rawHTML};

class FilterTheme extends Themelet
{
    public function addFilterBox(): void
    {
        global $config, $page, $user;

        // If user is not able to set their own filters, use the default filters.
        if ($user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $tags = $user->get_config()->get_string(
                FilterUserConfig::TAGS,
                $config->get_string(FilterConfig::TAGS)
            );
        } else {
            $tags = $config->get_string(FilterConfig::TAGS);
        }
        $html = "<noscript>Post filtering requires JavaScript</noscript>
        <ul id='filter-list' class='list-bulleted'></ul>
        <a id='disable-all-filters' style='display: none;' href='#'>Disable all</a>
        <a id='re-enable-all-filters' style='display: none;' href='#'>Re-enable all</a>
        ";
        $page->add_html_header(META(['id' => 'filter-tags', 'tags' => $tags]));
        $page->add_block(new Block("Filters", rawHTML($html), "left", 10));
    }
}
