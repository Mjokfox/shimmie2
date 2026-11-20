<?php

declare(strict_types=1);

namespace Shimmie2;

final class UploadPermission extends PermissionGroup
{
    public const KEY = "upload";

    #[PermissionMeta("Skip upload CAPTCHA")]
    public const SKIP_UPLOAD_CAPTCHA = "bypass_upload_captcha";

    #[PermissionMeta("Edit upload page")]
    public const EDIT_UPLOAD_PAGE = "edit_upload_page";
}
