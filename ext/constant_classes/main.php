<?php

declare(strict_types=1);

namespace Shimmie2;

class ConstantClasses extends Extension
{
    public function get_priority(): int
    {
        return 51;
    }
    public function onInitExt(InitExtEvent $event): void
    {
        new UserClass("moderator", "user", [
            BulkActionsPermission::PERFORM_BULK_ACTIONS => true,
            BulkDownloadPermission::BULK_DOWNLOAD => true,
            AliasEditorPermission::MANAGE_ALIAS_LIST => true,
            AutoTaggerPermission::MANAGE_AUTO_TAG => true,
            PostTagsPermission::MASS_TAG_EDIT => true,
            IPBanPermission::VIEW_IP => true,
            ReplaceFilePermission::REPLACE_IMAGE => true,
            BulkActionsPermission::BULK_EDIT_IMAGE_TAG => true,
            BulkActionsPermission::BULK_EDIT_IMAGE_SOURCE => true,
            WikiPermission::EDIT_WIKI_PAGE => true,
            RatingsPermission::BULK_EDIT_IMAGE_RATING => true,
            TrashPermission::VIEW_TRASH => true,
            TagCategoriesPermission::EDIT_TAG_CATEGORIES => true,
            ApprovalPermission::APPROVE_IMAGE => true,
            // Permissions::WIKI_ADMIN => true,
            // Permissions::FORUM_ADMIN => true,
            // Permissions::NOTES_ADMIN => true,
            // Permissions::POOLS_ADMIN => true,
        ]);
    }
}
