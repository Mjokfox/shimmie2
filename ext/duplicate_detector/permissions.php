<?php

declare(strict_types=1);

namespace Shimmie2;

final class DuplicateDetectorPermission extends PermissionGroup
{
    public const KEY = "replace_file";

    #[PermissionMeta("Replace duplicate post")]
    public const REPLACE_DUPLICATE = "replace_duplicate";
}
