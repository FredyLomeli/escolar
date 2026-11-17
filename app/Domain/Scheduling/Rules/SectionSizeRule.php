<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class SectionSizeRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $min = $context->settings->sectionSizeSoftMin;
        $max = $context->settings->sectionSizeSoftMax;
        $target = $context->settings->targetSectionSize;

        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->studentCount < $min) {
                $findings[] = Finding::create(
                    code: FindingCode::SectionSizeLow,
                    message: "La sección {$section->displayLabel()} tiene {$section->studentCount} alumnos, por debajo del mínimo sugerido ({$min}; objetivo ~{$target}).",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                );
            } elseif ($section->studentCount > $max) {
                $findings[] = Finding::create(
                    code: FindingCode::SectionSizeHigh,
                    message: "La sección {$section->displayLabel()} tiene {$section->studentCount} alumnos, por encima del máximo sugerido ({$max}; objetivo ~{$target}).",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                );
            }
        }

        return $findings;
    }
}
