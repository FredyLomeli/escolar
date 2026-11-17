<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\TimeRange;

final class ShiftWindowRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->meetings === []) {
                continue;
            }

            $shift = $context->shiftsById[$section->shiftId] ?? null;

            if ($shift === null || ! $shift->hasWindows()) {
                continue;
            }

            foreach ($section->meetings as $meeting) {
                $windows = $shift->windowsByDay[$meeting->dayOfWeek] ?? [];
                $covered = false;

                foreach ($windows as $window) {
                    if ($window->contains($meeting->timeRange)) {
                        $covered = true;
                        break;
                    }
                }

                if (! $covered) {
                    $day = TimeRange::dayLabel($meeting->dayOfWeek);

                    $findings[] = Finding::create(
                        code: FindingCode::OutsideShiftWindow,
                        message: "La sesión de {$section->displayLabel()} el {$day} ({$meeting->timeRange->format()}) queda fuera de las franjas del turno {$shift->name}.",
                        sectionIds: [$section->id],
                        entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                    );
                }
            }
        }

        return $findings;
    }
}
