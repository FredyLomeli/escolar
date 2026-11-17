<?php

namespace App\Domain\Scheduling\Data;

readonly class GroupTermSnapshot
{
    /**
     * @param  list<int>  $curriculumCourseIds
     * @param  array<int, float>  $weeklyHoursByCourse  Mapa course_id => weekly_hours
     */
    public function __construct(
        public int $id,
        public int $studentGroupId,
        public string $label,
        public int $termNumber,
        public int $studentCount,
        public string $programCode,
        public int $modalityId,
        public string $modalityName,
        public int $shiftId,
        public string $shiftName,
        public int $curriculumId,
        public array $curriculumCourseIds = [],
        public array $weeklyHoursByCourse = [],
        public ?int $expectedCourses = null,
    ) {}
}
