<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class DuplicateCrnRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<string, list<SectionSnapshot>> $sectionsByCrn */
        $sectionsByCrn = [];

        foreach ($context->sections as $section) {
            $crn = $section->crn !== null ? trim($section->crn) : '';

            if ($crn === '') {
                continue;
            }

            $sectionsByCrn[$crn][] = $section;
        }

        $findings = [];

        foreach ($sectionsByCrn as $crn => $sections) {
            if (count($sections) < 2) {
                continue;
            }

            $sectionIds = array_map(
                static fn (SectionSnapshot $section): int => $section->id,
                $sections,
            );

            $labels = implode(', ', array_map(
                static fn (SectionSnapshot $section): string => "{$section->courseCode} — {$section->courseName}",
                $sections,
            ));

            $findings[] = Finding::create(
                code: FindingCode::DuplicateCrn,
                message: "El CRN {$crn} está duplicado en este periodo entre las secciones: {$labels}.",
                sectionIds: $sectionIds,
                entity: new FindingEntity('section', $sections[0]->id, "CRN {$crn}"),
            );
        }

        return $findings;
    }
}
