<?php

declare(strict_types=1);

namespace Shimmie2;

class DuplicateDetectorConfig extends ConfigGroup
{
    public const KEY = "duplicate_detector";

    #[ConfigMeta("Enabled hash algorithms", ConfigType::ARRAY, options: [
        "average" => "average",
        "difference" => "difference",
        "perceptual" => "perceptual",
        // "blockhash" => "blockhash", // NOT WORKING
    ], default: ["difference", "perceptual"])]

    public const ENABLED_ALGORITHMS = "duplicate_detector_enabled_algorithms";

    #[ConfigMeta("Hamming distance threshold: ", ConfigType::INT, default: 8, help: "Hamming distance threshold when an image is considered duplicate automatically (higher can give more false negatives)")]
    public const HAMMING_DISTANCE_THRESHOLD = "duplicate_detector_hamming_distance_threshold";

    #[ConfigMeta("Default amount of reverse image search results: ", ConfigType::INT, default: 10)]
    public const DEFAULT_LIMIT = "duplicate_detector_results_default";
}
