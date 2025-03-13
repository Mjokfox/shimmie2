<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require_once "events.php";

class Index extends Extension
{
    public const KEY = "index";
    /** @var IndexTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $config, $page, $user;
        if (
            $event->page_matches("post/list", paged: true)
            || $event->page_matches("post/list/{search}", paged: true)
        ) {
            if ($event->get_GET('search')) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(search_link(Tag::explode($event->get_GET('search'), false)));
                return;
            }

            $search_terms = Tag::explode($event->get_arg('search', ""), false);
            $count_search_terms = count($search_terms);
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = $config->get_int(IndexConfig::IMAGES);

            $search_results_limit = $config->get_int(IndexConfig::SEARCH_RESULTS_LIMIT);

            if ($config->get_bool(IndexConfig::SIMPLE_BOTS_ONLY) && Network::is_bot()) {
                // Bots aren't allowed to use negative tags or wildcards at all
                foreach ($search_terms as $term) {
                    if ($term[0] == "-" || str_contains($term[0], "*")) {
                        throw new PermissionDenied("Bots are not allowed to use negative tags or wildcards");
                    }
                }

                // Bots love searching for weird combinations of tags - let's
                // limit them to only a few results for multi-tag searches
                if ($count_search_terms > 1) {
                    $search_results_limit = 100;
                }
            }

            if ($search_results_limit && $page_number > $search_results_limit / $page_size && !$user->can(IndexPermission::BIG_SEARCH)) {
                throw new PermissionDenied(
                    "Only $search_results_limit search results can be shown at once - " .
                    "if you want to find older posts, use more specific search terms"
                );
            }

            $total_pages = (int)ceil(Search::count_images($search_terms) / $config->get_int(IndexConfig::IMAGES));
            if ($search_results_limit && $total_pages > $search_results_limit / $page_size && !$user->can(IndexPermission::BIG_SEARCH)) {
                $total_pages = (int)ceil($search_results_limit / $page_size);
            }

            $images = null;
            if ($config->get_bool(IndexConfig::CACHE_FIRST_FEW)) {
                if ($count_search_terms === 0 && ($page_number < 10)) {
                    // extra caching for the first few post/list pages
                    $images = cache_get_or_set(
                        "post-list:$page_number",
                        fn () => Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms),
                        60
                    );
                }
            }
            if (is_null($images)) {
                $images = Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms);
            }

            $count_images = count($images);

            if ($count_search_terms === 0 && $count_images === 0 && $page_number === 1) {
                $this->theme->display_intro($page);
                send_event(new PostListBuildingEvent($search_terms));
            } elseif ($count_search_terms > 0 && $count_images === 1 && $page_number === 1) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link('post/view/'.$images[0]->id));
            } else {
                $plbe = send_event(new PostListBuildingEvent($search_terms));

                $this->theme->set_page($page_number, $total_pages, $search_terms);
                $this->theme->display_page($page, $images);
                if (count($plbe->parts) > 0) {
                    $this->theme->display_admin_block($plbe->parts);
                }
            }
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("posts", search_link(), "Posts", NavLink::is_active(["post","view"]), order: 20);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_all", search_link(), "All");
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_block(new Block("General", $this->theme->get_help_html()), 0);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('search')
            ->addArgument('query', InputArgument::REQUIRED)
            ->setDescription('Search the database and print results')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $query = Tag::explode($input->getArgument('query'));
                $items = Search::find_images(limit: 1000, tags: $query);
                foreach ($items as $item) {
                    $output->writeln($item->hash);
                }
                return Command::SUCCESS;
            });
        $event->app->register('debug:search')
            ->addArgument('query', InputArgument::REQUIRED)
            ->addOption('count', null, InputOption::VALUE_NONE, 'Generate a count-only query')
            ->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page number', default: 1)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of results per page', default: 25)
            ->setDescription('Show the SQL generated for a given search query')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $search = Tag::explode($input->getArgument('query'), false);
                $page = $input->getOption('page');
                $limit = $input->getOption('limit');
                $count = $input->getOption('count');

                $params = SearchParameters::from_terms($search);
                if ($count) {
                    $params->order = null;
                    $page = null;
                    $limit = null;
                }

                $q = Search::build_search_querylet(
                    $params,
                    $limit,
                    (int)(($page - 1) * $limit),
                    $count,
                );

                $sql_str = $q->sql;
                $sql_str = \Safe\preg_replace("/\s+/", " ", $sql_str);
                foreach ($q->variables as $key => $val) {
                    if (is_string($val)) {
                        $sql_str = str_replace(":$key", "'$val'", $sql_str);
                    } else {
                        $sql_str = str_replace(":$key", (string)$val, $sql_str);
                    }
                }
                $output->writeln(trim($sql_str));

                return Command::SUCCESS;
            });
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $database;

        if ($matches = $event->matches("/^filesize([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+[kmg]?b?)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = parse_shorthand_int($matches[2]);
            $event->add_querylet(new Querylet("images.filesize $cmp :val{$event->id}", ["val{$event->id}" => $val]));
        } elseif ($matches = $event->matches("/^id=([\d,]+)$/i")) {
            $val = array_map(fn ($x) => int_escape($x), explode(",", $matches[1]));
            $set = implode(",", $val);
            $event->add_querylet(new Querylet("images.id IN ($set)"));
        } elseif ($matches = $event->matches("/^id([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.id $cmp :val{$event->id}", ["val{$event->id}" => $val]));
        } elseif ($matches = $event->matches("/^(hash|md5)[=|:]([0-9a-fA-F]*)$/i")) {
            $hash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.hash = :hash', ["hash" => $hash]));
        } elseif ($matches = $event->matches("/^(phash)[=|:]([0-9a-fA-F]*)$/i")) {
            $phash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.phash = :phash', ["phash" => $phash]));
        } elseif ($matches = $event->matches("/^(filename|name)[=|:](.+)$/i")) {
            $filename = strtolower($matches[2]);
            $event->add_querylet(new Querylet("lower(images.filename) LIKE :filename{$event->id}", ["filename{$event->id}" => "%$filename%"]));
        } elseif ($matches = $event->matches("/^posted([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])([0-9-]*)$/i")) {
            // TODO Make this able to search = without needing a time component.
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = $matches[2];
            $event->add_querylet(new Querylet("images.posted $cmp :posted{$event->id}", ["posted{$event->id}" => $val]));
        } elseif ($matches = $event->matches("/^order[=|:](id|width|height|length|filesize|filename)[_]?(desc|asc)?$/i")) {
            $ord = strtolower($matches[1]);
            $default_order_for_column = \Safe\preg_match("/^(id|filename)$/", $matches[1]) ? "ASC" : "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.$ord $sort";
        } elseif ($matches = $event->matches("/^order[=|:]random[_]([0-9]{1,8})$/i")) {
            // requires a seed to avoid duplicates
            // since the tag can't be changed during the parseevent, we instead generate the seed during submit using js
            $seed = (int)$matches[1];
            $event->order = $database->seeded_random($seed, "images.id");
        } elseif ($matches = $event->matches("/^order[=|:]dailyshuffle$/i")) {
            // will use today's date as seed, thus allowing for a dynamic randomized list without outside intervention.
            // This way the list will change every day, giving a more dynamic feel to the imageboard.
            // recommended to change homepage to "post/list/order:dailyshuffle/1"
            $seed = (int)date("Ymd");
            $event->order = $database->seeded_random($seed, "images.id");
        }

        // If we've reached this far, and nobody else has done anything with this term, then treat it as a tag
        if (!is_null($event->term) && $event->order === null && $event->img_conditions == [] && $event->tag_conditions == []) {
            $event->add_tag_condition(new TagCondition($event->term, !$event->negative));
        }
    }

    public function get_priority(): int
    {
        // we want to turn a search term into a TagCondition only if nobody did anything else with that term
        return 95;
    }
}
