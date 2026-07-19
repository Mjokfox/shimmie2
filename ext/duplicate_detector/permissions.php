<?php

declare(strict_types=1);

namespace Shimmie2;

final class DuplicateDetectorPermission extends PermissionGroup
{
    public const KEY = "replace_file";

    #[PermissionMeta("Access the duplicate finder")]
    public const FIND_DUPLICATE = "find_duplicate";
    #[PermissionMeta("Replace duplicate post")]
    public const REPLACE_DUPLICATE = "replace_duplicate";
}
