<?php

declare(strict_types=1);

namespace Shimmie2;

class INatSourceInfo extends ExtensionInfo
{
    public const KEY = "inaturalist_source";

    public string $key = self::KEY;
    public string $name = "INaturalist Source";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public array $dependencies = [PostSourceInfo::KEY];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = 'Automatically adds the source from inaturalist if the filename is in accordance with "naturalist_{observation_id}_{image_id}_{image_index}.{ext}"';
    public ?string $documentation = 'To get such filenames automatically, use the userscript from<br><a href="https://github.com/Mjokfox/inaturalist_dl">https://github.com/Mjokfox/inaturalist_dl</a>';
}
