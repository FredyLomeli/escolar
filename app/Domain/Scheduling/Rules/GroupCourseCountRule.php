<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class GroupCourseCountRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<int, list<int>> $courseIdsByGroupTerm */
        $courseIdsByGroupTerm = [];
        /** @var array<int, list<int>> $sectionIdsByGroupTerm */
        $sectionIdsByGroupTerm = [];

        foreach ($context->sections as $section) {
            foreach ($section->groupTerms as $groupTerm) {
                $courseIdsByGroupTerm[$groupTerm->id][] = $section->courseId;
                $sectionIdsByGroupTerm[$groupTerm->id][] = $section->id;
            }
        }

        $findings = [];

        foreach ($context->groupTerms as $groupTerm) {
            if ($groupTerm->expectedCourses === null) {
                continue;
            }

            $actualCourses = count(array_unique($courseIdsByGroupTerm[$groupTerm->id] ?? []));

            if ($actualCourses === $groupTerm->expectedCourses) {
                continue;
            }

            $findings[] = Finding::create(
                code: FindingCode::GroupCourseCount,
                message: "El grupo {$groupTerm->label} ({$groupTerm->termNumber}.º cuatrimestre) tiene {$actualCourses} materia(s) asignada(s), pero su plan espera {$groupTerm->expectedCourses}.",
                sectionIds: $sectionIdsByGroupTerm[$groupTerm->id] ?? [],
                entity: new FindingEntity('group', $groupTerm->id, $groupTerm->label),
            );
        }

        return $findings;
    }
}
