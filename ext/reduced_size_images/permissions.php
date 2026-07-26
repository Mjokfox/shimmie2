<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReducedSizeImagesPermission extends PermissionGroup
{
    public const KEY = "reduced_size_images";

    #[PermissionMeta("See full size images")]
    public const SEE_FULL_SIZE = "see_full_size";
}
