<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReducedSizeImagesConfig extends ConfigGroup
{
    public const KEY = "reduced_size_images";

    #[ConfigMeta("Full size media URL format", ConfigType::STRING, advanced: true)]
    public const MEDIA_LINK = 'full_image_link';

    #[ConfigMeta("Reduce images to percentage size", ConfigType::INT, default: 75)]
    public const RESIZE_PERCENTAGE = 'resize_percentage';

    #[ConfigMeta("Minimum width", ConfigType::INT, default: 0)]
    public const MINIMUM_WIDTH = 'minimum_width';
    #[ConfigMeta("Minimum height", ConfigType::INT, default: 0, help: "Keep at 0 for no lower bound, might be necessary for e.g. pixelart")]
    public const MINIMUM_HEIGHT = 'minimum_height';
}
