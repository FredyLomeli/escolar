<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class CompactionValidityRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if (! $section->isCompacted()) {
                continue;
            }

            $modalityIds = [];
            $missingPlanGroups = [];

            foreach ($section->groupTerms as $groupTerm) {
                $modalityIds[$groupTerm->modalityId] = $groupTerm->modalityName;

                if (! in_array($section->courseId, $groupTerm->curriculumCourseIds, true)) {
                    $missingPlanGroups[] = $groupTerm->label;
                }
            }

            $reasons = [];

            if (count($modalityIds) > 1) {
                $modalitiesList = implode(' y ', array_values($modalityIds));
                $reasons[] = "mezcla grupos de distinta modalidad ({$modalitiesList})";
            }

            if ($missingPlanGroups !== []) {
                $groupsList = implode(', ', $missingPlanGroups);
                $reasons[] = "la materia no pertenece al plan de estudios de {$groupsList}";
            }

            if ($reasons !== []) {
                $detail = implode('; ', $reasons);
                $findings[] = Finding::create(
                    code: FindingCode::CompactionInvalid,
                    message: "La compactación de {$section->displayLabel()} no es válida porque {$detail}.",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                );
            }
        }

        return $findings;
    }
}
