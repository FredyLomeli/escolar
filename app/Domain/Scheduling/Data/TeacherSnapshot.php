<?php

namespace App\Domain\Scheduling\Data;

use App\Domain\Scheduling\TimeRange;
use App\Enums\TeacherOrigin;

readonly class TeacherSnapshot
{
    /**
     * @param  list<int>  $eligibleCourseIds
     * @param  array<int, list<TimeRange>>  $availableSlotsByDay  Mapa day_of_week => intervalos fusionados
     * @param  array<int, list<TimeRange>>  $unavailableSlotsByDay  Mapa day_of_week => intervalos no disponibles
     */
    public function __construct(
        public int $id,
        public string $employeeId,
        public string $fullName,
        public TeacherOrigin $origin,
        public ?int $maxWeeklyHours,
        public array $eligibleCourseIds = [],
        public bool $hasAvailabilityData = false,
        public array $availableSlotsByDay = [],
        public array $unavailableSlotsByDay = [],
    ) {}

    public function hasAvailableDeclarations(): bool
    {
        return $this->availableSlotsByDay !== [];
    }
}
