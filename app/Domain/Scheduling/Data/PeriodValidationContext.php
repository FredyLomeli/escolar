<?php

namespace App\Domain\Scheduling\Data;

readonly class PeriodValidationContext
{
    /**
     * @param  list<SectionSnapshot>  $sections
     * @param  list<GroupTermSnapshot>  $groupTerms
     * @param  array<int, TeacherSnapshot>  $teachersById
     * @param  array<int, ShiftSnapshot>  $shiftsById
     * @param  list<ClassroomSnapshot>  $classrooms
     */
    public function __construct(
        public PeriodSnapshot $period,
        public ValidationSettings $settings,
        public array $sections = [],
        public array $groupTerms = [],
        public array $teachersById = [],
        public array $shiftsById = [],
        public array $classrooms = [],
    ) {}
}
