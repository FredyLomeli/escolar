<?php

namespace App\Domain\Scheduling\Data;

readonly class ValidationSettings
{
    public function __construct(
        public int $targetSectionSize = 20,
        public int $sectionSizeSoftMin = 10,
        public int $sectionSizeSoftMax = 30,
        public bool $allowPublishWithWarnings = true,
    ) {}
}
