<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class TeacherEligibilityRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->teacherId === null) {
                continue;
            }

            $teacher = $context->teachersById[$section->teacherId] ?? null;

            if ($teacher === null) {
                continue;
            }

            if (in_array($section->courseId, $teacher->eligibleCourseIds, true)) {
                continue;
            }

            $teacherLabel = "{$teacher->fullName} ({$teacher->employeeId})";

            $findings[] = Finding::create(
                code: FindingCode::TeacherNotEligible,
                message: "El docente {$teacherLabel} no tiene la materia {$section->courseCode} — {$section->courseName} en su perfil ni en su historial.",
                sectionIds: [$section->id],
                entity: new FindingEntity('teacher', $teacher->id, $teacherLabel),
            );
        }

        return $findings;
    }
}
