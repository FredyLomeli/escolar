<?php

namespace App\Domain\Scheduling\Data;

use App\Domain\Scheduling\TimeRange;

readonly class MeetingSnapshot
{
    public function __construct(
        public int $id,
        public int $sectionId,
        public int $dayOfWeek,
        public TimeRange $timeRange,
        public ?int $classroomId,
        public ?string $classroomCode,
        public ?string $classroomName,
        public ?int $classroomCapacity,
        public int $startWeek,
        public int $endWeek,
    ) {}
}
