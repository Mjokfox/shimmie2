<?php

declare(strict_types=1);

namespace Shimmie2;

final class SearchParameters
{
    /**
     * @param TagCondition[] $tag_conditions
     * @param MetadataCondition[] $metadata_conditions
     */
    public function __construct(
        public readonly array $tag_conditions = [],
        public readonly array $metadata_conditions = [],
        public ?string $order = null,
    ) {
    }

    /**
     * Turn a human input string into a an abstract search query
     *
     * @param search-term-array $terms
     */
    public static function from_terms(array $terms): SearchParameters
    {
        $tag_conditions = [];
        $img_conditions = [];
        $order = null;

        $stpen = 0;  // search term parse event number
        $or_group = 0;
        $current_or_group = 0;
        foreach (array_merge([null], $terms) as $term) {
            if ($term === "(") {
                $or_group++;
                $current_or_group = $or_group;
            } elseif ($term === ")") {
                $current_or_group = 0;
            } else {
                $stpe = send_event(new SearchTermParseEvent($stpen++, $term, $terms, $current_or_group));
                $order ??= $stpe->order;
                $img_conditions = array_merge($img_conditions, $stpe->img_conditions);
                $tag_conditions = array_merge($tag_conditions, $stpe->tag_conditions);
            }
        }

        $order ??= "images.".Ctx::$config->get(IndexConfig::ORDER);

        return new SearchParameters($tag_conditions, $img_conditions, $order);
    }
}
