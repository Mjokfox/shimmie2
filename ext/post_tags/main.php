<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class TagSetException extends UserError
{
    public ?string $redirect;

    public function __construct(string $msg, ?string $redirect = null)
    {
        parent::__construct($msg);
        $this->redirect = $redirect;
    }
}

final class TagSetEvent extends Event
{
    public Image $image;
    /** @var list<tag-string> */
    public array $old_tags;
    /** @var list<tag-string> */
    public array $new_tags;
    /** @var list<tag-string> */
    public array $metatags;

    /**
     * @param tag-string[] $tags
     */
    public function __construct(Image $image, array $tags)
    {
        parent::__construct();
        $this->image    = $image;
        $this->old_tags = $image->get_tag_array();
        $this->new_tags = [];
        $this->metatags = [];

        foreach ($tags as $tag) {
            if ((!str_contains($tag, ':')) && (!str_contains($tag, '='))) {
                //Tag doesn't contain : or =, meaning it can't possibly be a metatag.
                //This should help speed wise, as it avoids running every single tag through a bunch of preg_match instead.
                $this->new_tags[] = $tag;
                continue;
            }

            $ttpe = send_event(new TagTermCheckEvent($tag));

            //seperate tags from metatags
            if (!$ttpe->metatag) {
                $this->new_tags[] = $tag;
            } else {
                $this->metatags[] = $tag;
            }
        }
    }
}

/**
 * Check whether or not a tag is a meta-tag
 */
final class TagTermCheckEvent extends Event
{
    public string $term;
    public bool $metatag = false;

    public function __construct(string $term)
    {
        parent::__construct();
        $this->term = $term;
    }

    /**
     * @return array<string>|null
     */
    public function matches(string $regex): ?array
    {
        $matches = [];
        if (\Safe\preg_match($regex, $this->term, $matches)) {
            return $matches;
        }
        return null;
    }
}

/**
 * If a tag is a meta-tag, parse it
 */
final class TagTermParseEvent extends Event
{
    public function __construct(
        public string $term,
        public int $image_id
    ) {
        parent::__construct();
    }

    /**
     * @return array<string>|null
     */
    public function matches(string $regex): ?array
    {
        $matches = [];
        if (\Safe\preg_match($regex, $this->term, $matches)) {
            if (!is_null($matches)) {
                return array_values($matches);
            }
        }
        return null;
    }
}

/** @extends Extension<PostTagsTheme> */
final class PostTags extends Extension
{
    public const KEY = "post_tags";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tag_edit/replace", method: "POST", permission: PostTagsPermission::MASS_TAG_EDIT)) {
            $this->mass_tag_edit($event->POST->req('search'), $event->POST->req('replace'), true);
            Ctx::$page->set_redirect(make_link("admin"));
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('tag-replace')
            ->addArgument('old_tag', InputArgument::REQUIRED)
            ->addArgument('new_tag', InputArgument::REQUIRED)
            ->setDescription('Mass edit tags')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $old_tag = $input->getArgument('old_tag');
                $new_tag = $input->getArgument('new_tag');
                $output->writeln("Mass editing tags: '$old_tag' -> '$new_tag'");
                $this->mass_tag_edit($old_tag, $new_tag, true);
                return Command::SUCCESS;
            });
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        if (
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG) && (
                isset($event->params['tags'])
                || isset($event->params["tags{$event->slot}"])
            )
        ) {
            $common_tags = $event->params['tags'] ?? "";
            $my_tags = $event->params["tags{$event->slot}"] ?? "";
            $tags = Tag::explode("$common_tags $my_tags");
            try {
                send_event(new TagSetEvent($event->image, $tags));
            } catch (TagSetException $e) {
                if ($e->redirect) {
                    Ctx::$page->flash("{$e->getMessage()}, please see {$e->redirect}");
                } else {
                    Ctx::$page->flash($e->getMessage());
                }
                throw $e;
            }
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^tags([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $count = $matches[2];
            $event->add_querylet(
                new Querylet("EXISTS (
				              SELECT 1
				              FROM image_tags it
				              LEFT JOIN tags t ON it.tag_id = t.id
				              WHERE images.id = it.image_id
				              GROUP BY image_id
				              HAVING COUNT(*) $cmp $count
				)")
            );
        }
    }

    public function onTagSet(TagSetEvent $event): void
    {
        if (Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG) && (!$event->image->is_locked() || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK))) {
            $event->image->set_tags($event->new_tags);
        }
        foreach ($event->metatags as $tag) {
            send_event(new TagTermParseEvent($tag, $event->image->id));
        }
    }
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $event->image->delete_tags_from_image();
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_mass_editor();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('ext_doc/post_tags'), "Help");
        }
    }

    /**
     * When an alias is added, oldtag becomes inaccessible.
     */
    public function onAddAlias(AddAliasEvent $event): void
    {
        $this->mass_tag_edit($event->oldtag, $event->newtag, false);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_tag_editor_html($event->image), 40);
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        // get_tag_list can trigger a database query,
        // so we only want to do it if we need to
        if (str_contains($event->link, '$tags')) {
            $tags = $event->image->get_tag_list();
            $tags = str_replace("/", "", $tags);
            $tags = ltrim($tags, ".");
            $event->replace('$tags', $tags);
        }
    }


    public function onUploadHeaderBuilding(UploadHeaderBuildingEvent $event): void
    {
        $event->add_part("Tags", 10);
    }

    public function onUploadCommonBuilding(UploadCommonBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_common_html(), 10);
    }

    public function onUploadSpecificBuilding(UploadSpecificBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_specific_html($event->suffix), 10);
    }

    private function mass_tag_edit(string $search, string $replace, bool $commit): void
    {
        global $database;

        $search_set = Tag::explode(strtolower($search), false);
        $replace_set = Tag::explode(strtolower($replace), false);

        Log::info("tag_edit", "Mass editing tags: '$search' -> '$replace'");

        if (count($search_set) === 1 && count($replace_set) === 1) {
            $images = Search::find_images(limit: 10, terms: $replace_set);
            if (count($images) === 0) {
                Log::info("tag_edit", "No images found with target tag, doing in-place rename");
                $database->execute(
                    "DELETE FROM tags WHERE tag=:replace",
                    ["replace" => $replace_set[0]]
                );
                $database->execute(
                    "UPDATE tags SET tag=:replace WHERE tag=:search",
                    ["replace" => $replace_set[0], "search" => $search_set[0]]
                );
                return;
            }
        }

        $last_id = -1;
        while (true) {
            // make sure we don't look at the same images twice.
            // search returns high-ids first, so we want to look
            // at images with lower IDs than the previous.
            $search_forward = $search_set;
            $search_forward[] = "order=id_desc"; //Default order can be changed, so make sure we order high > low ID
            if ($last_id >= 0) {
                $search_forward[] = "id<$last_id";
            }

            $images = Search::find_images(limit: 100, terms: $search_forward);
            if (count($images) === 0) {
                break;
            }

            foreach ($images as $image) {
                $before = array_filter(array_map(strtolower(...), $image->get_tag_array()), fn ($tag) => !empty($tag));
                $after = array_merge(array_diff($before, $search_set), $replace_set);
                send_event(new TagSetEvent($image, $after));
                $last_id = $image->id;
            }
            if ($commit) {
                // Mass tag edit can take longer than the page timeout,
                // so we need to commit periodically to save what little
                // work we've done and avoid starting from scratch.
                $database->commit();
                $database->begin_transaction();
            }
        }
    }
}
