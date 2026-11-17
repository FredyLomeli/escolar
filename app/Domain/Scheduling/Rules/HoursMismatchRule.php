<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Enums\DeliveryType;

final class HoursMismatchRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->delivery === DeliveryType::OnlineAsync) {
                continue;
            }

            if ($section->expectedWeeklyHours === null) {
                continue;
            }

            // Si aún no tiene sesiones, SectionWithoutSchedule ya reporta la falta de horario.
            if ($section->meetings === []) {
                continue;
            }

            $actualHours = round($section->totalMeetingHours(), 2);
            $expectedHours = round($section->expectedWeeklyHours, 2);

            if (abs($actualHours - $expectedHours) < 0.01) {
                continue;
            }

            $actualFormatted = $this->formatHours($actualHours);
            $expectedFormatted = $this->formatHours($expectedHours);

            $findings[] = Finding::create(
                code: FindingCode::HoursMismatch,
                message: "La sección {$section->displayLabel()} suma {$actualFormatted} h semanales, pero su plan indica {$expectedFormatted} h.",
                sectionIds: [$section->id],
                entity: new FindingEntity('section', $section->id, $section->displayLabel()),
            );
        }

        return $findings;
    }

    private function formatHours(float $hours): string
    {
        return fmod($hours, 1.0) === 0.0
            ? (string) (int) $hours
            : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
    }
}
