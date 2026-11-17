<?php

namespace App\Domain\Planning;

readonly class AdvancePeriodInput
{
    /**
     * @param  array<int, int>  $studentCountOverrides  Mapa student_group_id => student_count
     * @param  list<int>  $excludedGroupIds
     */
    public function __construct(
        public string $code,
        public string $name,
        public int $year,
        public int $termOfYear,
        public string $startsOn,
        public string $endsOn,
        public int $weeks,
        public int $sourcePeriodId,
        public bool $copyMeetings = false,
        public bool $suggestTeachers = true,
        public array $studentCountOverrides = [],
        public array $excludedGroupIds = [],
    ) {}
}
