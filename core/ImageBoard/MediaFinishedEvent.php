<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A new post has been added and put in the warehouse
 */
final class MediaFinishedEvent extends Event
{
    public function __construct(
        public Post $post,
    ) {
        parent::__construct();
    }
}
