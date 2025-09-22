<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A new image has been added and put in the warehouse
 */
final class ImageFinishedEvent extends Event
{
    /**
     * A new image has been added and put in the warehouse
     */
    public function __construct(
        public Image $image,
    ) {
        parent::__construct();
    }
}
