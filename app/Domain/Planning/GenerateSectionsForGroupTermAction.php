<?php

namespace App\Domain\Planning;

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\ModalityCode;
use App\Enums\SectionStatus;
use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GenerateSectionsForGroupTermAction
{
    /**
     * Genera las secciones correspondientes al plan de estudios de un GroupTerm.
     *
     * @return Collection<int, Section>
     */
    public function execute(
        GroupTerm $groupTerm,
        AcademicPeriod $targetPeriod,
        bool $suggestTeachers = true,
        ?AcademicPeriod $sourcePeriod = null,
        bool $copyMeetings = false,
    ): Collection {
        $groupTerm->loadMissing([
            'studentGroup.program.modality',
            'studentGroup.curriculum.curriculumCourses',
        ]);

        $group = $groupTerm->studentGroup;
        $termCourses = $group->curriculum->curriculumCourses
            ->where('term_number', $groupTerm->term_number)
            ->values();

        if ($termCourses->isEmpty()) {
            return collect();
        }

        $isRegular = $group->program->modality->code === ModalityCode::Regular;
        $defaultWeeks = $isRegular
            ? (int) Setting::getValue('weeks_in_term_regular', 14)
            : (int) Setting::getValue('weeks_in_module_executive', 5);

        $endWeek = max(1, min($targetPeriod->weeks, $defaultWeeks));

        $courseIds = $termCourses->pluck('course_id')->values()->all();

        $suggestedTeachersByCourse = $suggestTeachers
            ? $this->latestTeachersByCourse($courseIds)
            : [];

        $previousMeetingsByCourse = ($copyMeetings && $sourcePeriod !== null)
            ? $this->previousMeetingsByCourse($group->id, $sourcePeriod->id, $courseIds)
            : [];

        $created = collect();

        foreach ($termCourses as $curriculumCourse) {
            $delivery = $curriculumCourse->default_delivery;

            // Las materias en línea externas o asíncronas (@ / VV) no asignan docente de sede por defecto.
            $allowsSuggestedTeacher = ! in_array($delivery, [
                DeliveryType::OnlineLive,
                DeliveryType::OnlineAsync,
            ], true);

            $teacherId = $allowsSuggestedTeacher
                ? ($suggestedTeachersByCourse[$curriculumCourse->course_id] ?? null)
                : null;

            $section = Section::query()->create([
                'academic_period_id' => $targetPeriod->id,
                'course_id' => $curriculumCourse->course_id,
                'crn' => null,
                'teacher_id' => $teacherId,
                'delivery' => $delivery,
                'shift_id' => $group->shift_id,
                'start_week' => 1,
                'end_week' => $endWeek,
                'status' => SectionStatus::Draft,
            ]);

            SectionGroup::query()->create([
                'section_id' => $section->id,
                'group_term_id' => $groupTerm->id,
            ]);

            if (isset($previousMeetingsByCourse[$curriculumCourse->course_id])) {
                foreach ($previousMeetingsByCourse[$curriculumCourse->course_id] as $meeting) {
                    SectionMeeting::query()->create([
                        'section_id' => $section->id,
                        'day_of_week' => $meeting->day_of_week,
                        'starts_at' => $meeting->starts_at,
                        'ends_at' => $meeting->ends_at,
                        'classroom_id' => $meeting->classroom_id,
                    ]);
                }
            }

            $created->push($section);
        }

        return $created;
    }

    /**
     * Obtiene el último docente activo que impartió cada materia en periodos históricos.
     *
     * @param  list<int>  $courseIds
     * @return array<int, int>
     */
    private function latestTeachersByCourse(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $sections = Section::query()
            ->select('sections.course_id', 'sections.teacher_id')
            ->join('academic_periods', 'academic_periods.id', '=', 'sections.academic_period_id')
            ->join('teachers', 'teachers.id', '=', 'sections.teacher_id')
            ->whereIn('sections.course_id', $courseIds)
            ->whereNotNull('sections.teacher_id')
            ->where('teachers.active', true)
            ->whereIn('academic_periods.status', [
                AcademicPeriodStatus::Published->value,
                AcademicPeriodStatus::Closed->value,
            ])
            ->orderByDesc('academic_periods.year')
            ->orderByDesc('academic_periods.term_of_year')
            ->orderByDesc('sections.id')
            ->get();

        $map = [];

        foreach ($sections as $section) {
            if (! isset($map[$section->course_id]) && $section->teacher_id !== null) {
                $map[$section->course_id] = $section->teacher_id;
            }
        }

        return $map;
    }

    /**
     * Obtiene las sesiones previas del mismo grupo y materia en el periodo origen.
     *
     * @param  list<int>  $courseIds
     * @return array<int, EloquentCollection<int, SectionMeeting>>
     */
    private function previousMeetingsByCourse(int $studentGroupId, int $sourcePeriodId, array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $sourceSections = Section::query()
            ->with('meetings')
            ->where('academic_period_id', $sourcePeriodId)
            ->whereIn('course_id', $courseIds)
            ->whereHas('groupTerms', function ($query) use ($studentGroupId): void {
                $query->where('student_group_id', $studentGroupId);
            })
            ->get();

        $map = [];

        foreach ($sourceSections as $section) {
            if (! isset($map[$section->course_id]) && $section->meetings->isNotEmpty()) {
                $map[$section->course_id] = $section->meetings;
            }
        }

        return $map;
    }
}
