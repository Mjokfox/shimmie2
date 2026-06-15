<?php

declare(strict_types=1);

namespace Shimmie2;

class DuplicateCheckEvent extends Event
{
    public bool $is_duplicate = false;
    /**
     * @param Path $file
     */
    public function __construct(
        public Path $file,
        public int $image_id
    ) {
        parent::__construct();
    }
}
