<?php

namespace App\Domain\Scheduling;

use App\Domain\Scheduling\Data\ClassroomSnapshot;
use App\Domain\Scheduling\Data\GroupTermSnapshot;
use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\Data\PeriodSnapshot;
use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\ReservedBlockSnapshot;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Data\ShiftSnapshot;
use App\Domain\Scheduling\Data\TeacherSnapshot;
use App\Domain\Scheduling\Data\ValidationSettings;
use App\Enums\AcademicPeriodStatus;
use App\Enums\AvailabilityType;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Teacher;
use Illuminate\Support\Collection;

class PeriodValidationContextFactory
{
    public function build(AcademicPeriod $period): PeriodValidationContext
    {
        $period->load([
            'sections.course',
            'sections.meetings.classroom',
            'sections.groupTerms.studentGroup.program.modality',
            'sections.groupTerms.studentGroup.shift',
            'sections.groupTerms.studentGroup.curriculum.curriculumCourses',
            'sections.groupTerms.studentGroup.curriculum.termRules',
            'groupTerms.studentGroup.program.modality',
            'groupTerms.studentGroup.shift',
            'groupTerms.studentGroup.curriculum.curriculumCourses',
            'groupTerms.studentGroup.curriculum.termRules',
        ]);

        $settings = $this->loadSettings();

        $groupTermSnapshotsById = [];

        foreach ($period->groupTerms as $groupTerm) {
            $groupTermSnapshotsById[$groupTerm->id] = $this->mapGroupTerm($groupTerm);
        }

        foreach ($period->sections as $section) {
            foreach ($section->groupTerms as $groupTerm) {
                $groupTermSnapshotsById[$groupTerm->id] ??= $this->mapGroupTerm($groupTerm);
            }
        }

        $shiftIds = $period->sections->pluck('shift_id')
            ->merge(array_map(static fn (GroupTermSnapshot $gt): int => $gt->shiftId, $groupTermSnapshotsById))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $shiftsById = $this->loadShifts($shiftIds);

        $teacherIds = $period->sections->pluck('teacher_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $teachersById = $this->loadTeachers($teacherIds, $period->id);

        $classrooms = Classroom::query()
            ->where('active', true)
            ->orderBy('capacity')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity', 'active'])
            ->map(static fn (Classroom $classroom): ClassroomSnapshot => new ClassroomSnapshot(
                id: $classroom->id,
                code: $classroom->code,
                name: $classroom->name,
                capacity: $classroom->capacity,
                active: $classroom->active,
            ))
            ->values()
            ->all();

        $sectionSnapshots = $period->sections
            ->map(function (Section $section) use ($groupTermSnapshotsById): SectionSnapshot {
                $groupTerms = [];
                $expectedWeeklyHours = null;

                foreach ($section->groupTerms as $groupTerm) {
                    $snapshot = $groupTermSnapshotsById[$groupTerm->id];
                    $groupTerms[] = $snapshot;

                    if ($expectedWeeklyHours === null && isset($snapshot->weeklyHoursByCourse[$section->course_id])) {
                        $expectedWeeklyHours = $snapshot->weeklyHoursByCourse[$section->course_id];
                    }
                }

                $meetings = $section->meetings
                    ->map(static fn ($meeting): MeetingSnapshot => new MeetingSnapshot(
                        id: $meeting->id,
                        sectionId: $section->id,
                        dayOfWeek: $meeting->day_of_week,
                        timeRange: TimeRange::fromTimeStrings((string) $meeting->starts_at, (string) $meeting->ends_at),
                        classroomId: $meeting->classroom_id,
                        classroomCode: $meeting->classroom?->code,
                        classroomName: $meeting->classroom?->name,
                        classroomCapacity: $meeting->classroom?->capacity,
                        startWeek: $section->start_week,
                        endWeek: $section->end_week,
                    ))
                    ->values()
                    ->all();

                return new SectionSnapshot(
                    id: $section->id,
                    courseId: $section->course_id,
                    courseCode: $section->course->fullCode(),
                    courseName: $section->course->name,
                    crn: $section->crn,
                    delivery: $section->delivery,
                    shiftId: $section->shift_id,
                    startWeek: $section->start_week,
                    endWeek: $section->end_week,
                    studentCount: $section->studentCount(),
                    teacherId: $section->teacher_id,
                    groupTerms: $groupTerms,
                    meetings: $meetings,
                    expectedWeeklyHours: $expectedWeeklyHours,
                );
            })
            ->values()
            ->all();

        return new PeriodValidationContext(
            period: new PeriodSnapshot(
                id: $period->id,
                code: $period->code,
                name: $period->name,
                weeks: $period->weeks,
                status: $period->status,
            ),
            settings: $settings,
            sections: $sectionSnapshots,
            groupTerms: array_values($groupTermSnapshotsById),
            teachersById: $teachersById,
            shiftsById: $shiftsById,
            classrooms: $classrooms,
        );
    }

    private function loadSettings(): ValidationSettings
    {
        $rows = Setting::query()
            ->whereIn('key', [
                'target_section_size',
                'section_size_soft_min',
                'section_size_soft_max',
                'allow_publish_with_warnings',
            ])
            ->get()
            ->keyBy('key');

        $extract = static function (Collection $rows, string $key, mixed $default): mixed {
            $setting = $rows->get($key);

            if ($setting === null) {
                return $default;
            }

            $raw = $setting->value;

            return is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
        };

        return new ValidationSettings(
            targetSectionSize: (int) $extract($rows, 'target_section_size', 20),
            sectionSizeSoftMin: (int) $extract($rows, 'section_size_soft_min', 10),
            sectionSizeSoftMax: (int) $extract($rows, 'section_size_soft_max', 30),
            allowPublishWithWarnings: (bool) $extract($rows, 'allow_publish_with_warnings', true),
        );
    }

    private function mapGroupTerm(GroupTerm $groupTerm): GroupTermSnapshot
    {
        $group = $groupTerm->studentGroup;
        $program = $group->program;
        $shift = $group->shift;
        $curriculum = $group->curriculum;

        $label = $group->label ?: "{$program->code} · {$groupTerm->term_number}.º";

        $curriculumCourseIds = [];
        $weeklyHoursByCourse = [];

        foreach ($curriculum->curriculumCourses as $curriculumCourse) {
            $curriculumCourseIds[] = $curriculumCourse->course_id;

            if ($curriculumCourse->weekly_hours !== null) {
                $weeklyHoursByCourse[$curriculumCourse->course_id] = (float) $curriculumCourse->weekly_hours;
            }
        }

        $termRule = $curriculum->termRules
            ->firstWhere('term_number', $groupTerm->term_number);

        return new GroupTermSnapshot(
            id: $groupTerm->id,
            studentGroupId: $group->id,
            label: $label,
            termNumber: $groupTerm->term_number,
            studentCount: $groupTerm->student_count,
            programCode: $program->code,
            modalityId: $program->modality_id,
            modalityName: $program->modality->name,
            shiftId: $shift->id,
            shiftName: $shift->name,
            curriculumId: $curriculum->id,
            curriculumCourseIds: array_values(array_unique($curriculumCourseIds)),
            weeklyHoursByCourse: $weeklyHoursByCourse,
            expectedCourses: $termRule?->expected_courses,
        );
    }

    /**
     * @param  list<int>  $shiftIds
     * @return array<int, ShiftSnapshot>
     */
    private function loadShifts(array $shiftIds): array
    {
        if ($shiftIds === []) {
            return [];
        }

        $shifts = Shift::query()
            ->with(['windows', 'reservedBlocks'])
            ->whereIn('id', $shiftIds)
            ->get();

        $map = [];

        foreach ($shifts as $shift) {
            $rawWindowsByDay = [];

            foreach ($shift->windows as $window) {
                $rawWindowsByDay[$window->day_of_week][] = TimeRange::fromTimeStrings(
                    (string) $window->starts_at,
                    (string) $window->ends_at,
                );
            }

            $windowsByDay = [];

            foreach ($rawWindowsByDay as $day => $intervals) {
                $windowsByDay[$day] = TimeRange::mergeIntervals($intervals);
            }

            $reservedBlocksByDay = [];

            foreach ($shift->reservedBlocks as $block) {
                $reservedBlocksByDay[$block->day_of_week][] = new ReservedBlockSnapshot(
                    id: $block->id,
                    name: $block->name,
                    dayOfWeek: $block->day_of_week,
                    timeRange: TimeRange::fromTimeStrings((string) $block->starts_at, (string) $block->ends_at),
                );
            }

            $map[$shift->id] = new ShiftSnapshot(
                id: $shift->id,
                modalityId: $shift->modality_id,
                name: $shift->name,
                windowsByDay: $windowsByDay,
                reservedBlocksByDay: $reservedBlocksByDay,
            );
        }

        return $map;
    }

    /**
     * @param  list<int>  $teacherIds
     * @return array<int, TeacherSnapshot>
     */
    private function loadTeachers(array $teacherIds, int $academicPeriodId): array
    {
        if ($teacherIds === []) {
            return [];
        }

        $teachers = Teacher::query()
            ->with([
                'teacherCourses',
                'availabilities' => static function ($query) use ($academicPeriodId): void {
                    $query->whereNull('academic_period_id')
                        ->orWhere('academic_period_id', $academicPeriodId);
                },
            ])
            ->whereIn('id', $teacherIds)
            ->get();

        $historicalRows = Section::query()
            ->select('sections.teacher_id', 'sections.course_id')
            ->join('academic_periods', 'academic_periods.id', '=', 'sections.academic_period_id')
            ->whereIn('sections.teacher_id', $teacherIds)
            ->where('sections.academic_period_id', '!=', $academicPeriodId)
            ->whereIn('academic_periods.status', [
                AcademicPeriodStatus::Published->value,
                AcademicPeriodStatus::Closed->value,
            ])
            ->distinct()
            ->get();

        /** @var array<int, list<int>> $historyByTeacher */
        $historyByTeacher = [];

        foreach ($historicalRows as $row) {
            if ($row->teacher_id !== null) {
                $historyByTeacher[$row->teacher_id][] = $row->course_id;
            }
        }

        $map = [];

        foreach ($teachers as $teacher) {
            $eligible = array_values(array_unique(array_merge(
                $teacher->teacherCourses->pluck('course_id')->all(),
                $historyByTeacher[$teacher->id] ?? [],
            )));

            $periodAvailabilities = $teacher->availabilities
                ->where('academic_period_id', $academicPeriodId);

            $scopedAvailabilities = $periodAvailabilities->isNotEmpty()
                ? $periodAvailabilities
                : $teacher->availabilities->whereNull('academic_period_id');

            $rawAvailableByDay = [];
            $unavailableByDay = [];

            foreach ($scopedAvailabilities as $availability) {
                $range = TimeRange::fromTimeStrings((string) $availability->starts_at, (string) $availability->ends_at);

                if ($availability->type === AvailabilityType::Available) {
                    $rawAvailableByDay[$availability->day_of_week][] = $range;
                } else {
                    $unavailableByDay[$availability->day_of_week][] = $range;
                }
            }

            $availableByDay = [];

            foreach ($rawAvailableByDay as $day => $intervals) {
                $availableByDay[$day] = TimeRange::mergeIntervals($intervals);
            }

            $map[$teacher->id] = new TeacherSnapshot(
                id: $teacher->id,
                employeeId: $teacher->employee_id,
                fullName: $teacher->full_name,
                origin: $teacher->origin,
                maxWeeklyHours: $teacher->max_weekly_hours,
                eligibleCourseIds: $eligible,
                hasAvailabilityData: $scopedAvailabilities->isNotEmpty(),
                availableSlotsByDay: $availableByDay,
                unavailableSlotsByDay: $unavailableByDay,
            );
        }

        return $map;
    }
}
