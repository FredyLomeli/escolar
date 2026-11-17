<?php

namespace App\Domain\Scheduling\Data;

use App\Domain\Scheduling\TimeRange;

readonly class ReservedBlockSnapshot
{
    public function __construct(
        public int $id,
        public string $name,
        public int $dayOfWeek,
        public TimeRange $timeRange,
    ) {}
}
