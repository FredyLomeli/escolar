<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class SectionTeacherPresenceRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            if ($section->teacherId !== null) {
                continue;
            }

            $code = $section->delivery->isOnline()
                ? FindingCode::OnlineNoTeacher
                : FindingCode::SectionWithoutTeacher;

            $message = $section->delivery->isOnline()
                ? "La sección en línea {$section->displayLabel()} ({$section->delivery->label()}) no tiene docente asignado (esperado cuando lo asigna sede central)."
                : "La sección presencial {$section->displayLabel()} no tiene docente asignado.";

            $findings[] = Finding::create(
                code: $code,
                message: $message,
                sectionIds: [$section->id],
                entity: new FindingEntity('section', $section->id, $section->displayLabel()),
            );
        }

        return $findings;
    }
}
