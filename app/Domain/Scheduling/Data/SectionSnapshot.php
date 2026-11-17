<?php

namespace App\Domain\Scheduling\Data;

use App\Enums\DeliveryType;

readonly class SectionSnapshot
{
    /**
     * @param  list<GroupTermSnapshot>  $groupTerms
     * @param  list<MeetingSnapshot>  $meetings
     */
    public function __construct(
        public int $id,
        public int $courseId,
        public string $courseCode,
        public string $courseName,
        public ?string $crn,
        public DeliveryType $delivery,
        public int $shiftId,
        public int $startWeek,
        public int $endWeek,
        public int $studentCount,
        public ?int $teacherId = null,
        public array $groupTerms = [],
        public array $meetings = [],
        public ?float $expectedWeeklyHours = null,
    ) {}

    public function displayLabel(): string
    {
        $crnSuffix = $this->crn !== null && $this->crn !== '' ? " (CRN {$this->crn})" : '';

        return "{$this->courseCode} — {$this->courseName}{$crnSuffix}";
    }

    public function isCompacted(): bool
    {
        return count($this->groupTerms) > 1;
    }

    public function totalMeetingHours(): float
    {
        $minutes = 0;

        foreach ($this->meetings as $meeting) {
            $minutes += $meeting->timeRange->durationMinutes();
        }

        return $minutes / 60.0;
    }
}
