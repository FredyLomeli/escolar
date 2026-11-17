<?php

namespace App\Domain\Planning;

use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;

class PreviewPeriodAdvancementAction
{
    /**
     * Calcula qué grupos activos avanzan al siguiente cuatrimestre y cuáles egresan,
     * sin persistir cambios en la base de datos.
     *
     * @return array{
     *     advancing: list<array{
     *         student_group_id: int,
     *         label: string,
     *         program_code: string,
     *         program_name: string,
     *         shift_name: string,
     *         current_term: int,
     *         next_term: int,
     *         student_count: int,
     *         estimated_sections: int,
     *     }>,
     *     graduating: list<array{
     *         student_group_id: int,
     *         label: string,
     *         program_code: string,
     *         program_name: string,
     *         shift_name: string,
     *         current_term: int,
     *         student_count: int,
     *     }>,
     * }
     */
    public function execute(AcademicPeriod $sourcePeriod): array
    {
        $sourcePeriod->loadMissing([
            'groupTerms.studentGroup.program',
            'groupTerms.studentGroup.curriculum.curriculumCourses',
            'groupTerms.studentGroup.shift',
        ]);

        $advancing = [];
        $graduating = [];

        foreach ($sourcePeriod->groupTerms as $groupTerm) {
            $group = $groupTerm->studentGroup;

            if ($group->status !== StudentGroupStatus::Active) {
                continue;
            }

            $label = $group->label ?: "{$group->program->code} ({$group->shift->name})";

            if ($groupTerm->term_number >= 9) {
                $graduating[] = [
                    'student_group_id' => $group->id,
                    'label' => $label,
                    'program_code' => $group->program->code,
                    'program_name' => $group->program->name,
                    'shift_name' => $group->shift->name,
                    'current_term' => $groupTerm->term_number,
                    'student_count' => $groupTerm->student_count,
                ];

                continue;
            }

            $nextTerm = $groupTerm->term_number + 1;
            $estimatedSections = $group->curriculum->curriculumCourses
                ->where('term_number', $nextTerm)
                ->count();

            $advancing[] = [
                'student_group_id' => $group->id,
                'label' => $label,
                'program_code' => $group->program->code,
                'program_name' => $group->program->name,
                'shift_name' => $group->shift->name,
                'current_term' => $groupTerm->term_number,
                'next_term' => $nextTerm,
                'student_count' => $groupTerm->student_count,
                'estimated_sections' => $estimatedSections,
            ];
        }

        return [
            'advancing' => $advancing,
            'graduating' => $graduating,
        ];
    }
}
