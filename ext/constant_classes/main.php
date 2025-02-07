<?php

declare(strict_types=1);

namespace Shimmie2;

new UserClass("moderator", "user", [
    Permissions::PERFORM_BULK_ACTIONS => true,
    Permissions::BULK_DOWNLOAD => true,
    Permissions::MANAGE_ALIAS_LIST => true,
    Permissions::MANAGE_AUTO_TAG => true,
    Permissions::MASS_TAG_EDIT => true,
    Permissions::VIEW_IP => true,
    Permissions::REPLACE_IMAGE => true,
    Permissions::BULK_EDIT_IMAGE_TAG => true,
    Permissions::BULK_EDIT_IMAGE_SOURCE => true,
    Permissions::EDIT_WIKI_PAGE => true,
    Permissions::BULK_EDIT_IMAGE_RATING => true,
    Permissions::VIEW_TRASH => true,
    Permissions::EDIT_TAG_CATEGORIES => true,
    Permissions::APPROVE_IMAGE => true,
    Permissions::APPROVE_COMMENT => true,
    // Permissions::WIKI_ADMIN => true,
    // Permissions::FORUM_ADMIN => true,
    // Permissions::NOTES_ADMIN => true,
    // Permissions::POOLS_ADMIN => true,
]);
