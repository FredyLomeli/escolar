<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class CapacityExceededRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            $reportedClassrooms = [];

            foreach ($section->meetings as $meeting) {
                if ($meeting->classroomId === null || $meeting->classroomCapacity === null) {
                    continue;
                }

                if ($section->studentCount <= $meeting->classroomCapacity) {
                    continue;
                }

                if (isset($reportedClassrooms[$meeting->classroomId])) {
                    continue;
                }

                $reportedClassrooms[$meeting->classroomId] = true;
                $classroomLabel = $meeting->classroomCode !== null
                    ? "{$meeting->classroomCode} — {$meeting->classroomName}"
                    : "Aula #{$meeting->classroomId}";

                $findings[] = Finding::create(
                    code: FindingCode::CapacityExceeded,
                    message: "La sección {$section->displayLabel()} tiene {$section->studentCount} alumnos y excede el cupo del aula {$classroomLabel} (capacidad {$meeting->classroomCapacity}).",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('classroom', $meeting->classroomId, $classroomLabel),
                );
            }
        }

        return $findings;
    }
}
