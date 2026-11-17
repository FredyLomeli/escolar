<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\TimeRange;

final class ReservedBlockConflictRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->meetings === []) {
                continue;
            }

            $shiftIds = $section->groupTerms !== []
                ? array_values(array_unique(array_map(
                    static fn ($groupTerm): int => $groupTerm->shiftId,
                    $section->groupTerms,
                )))
                : [$section->shiftId];

            $reported = [];

            foreach ($shiftIds as $shiftId) {
                $shift = $context->shiftsById[$shiftId] ?? null;

                if ($shift === null) {
                    continue;
                }

                foreach ($section->meetings as $meeting) {
                    $blocks = $shift->reservedBlocksByDay[$meeting->dayOfWeek] ?? [];

                    foreach ($blocks as $block) {
                        if (! $meeting->timeRange->overlaps($block->timeRange)) {
                            continue;
                        }

                        $key = "{$shiftId}:{$block->id}:{$meeting->id}";

                        if (isset($reported[$key])) {
                            continue;
                        }

                        $reported[$key] = true;
                        $day = TimeRange::dayLabel($meeting->dayOfWeek);

                        $findings[] = Finding::create(
                            code: FindingCode::ReservedBlockConflict,
                            message: "La sesión de {$section->displayLabel()} el {$day} ({$meeting->timeRange->format()}) invade el bloque reservado \"{$block->name}\" ({$block->timeRange->format()}) del turno {$shift->name}.",
                            sectionIds: [$section->id],
                            entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                        );
                    }
                }
            }
        }

        return $findings;
    }
}
