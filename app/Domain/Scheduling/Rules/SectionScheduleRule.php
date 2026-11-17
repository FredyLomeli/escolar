<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Enums\DeliveryType;

final class SectionScheduleRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];

        foreach ($context->sections as $section) {
            // Las secciones en línea asíncronas (@) no requieren sesiones presenciales ni aula física.
            if ($section->delivery === DeliveryType::OnlineAsync) {
                continue;
            }

            if ($section->meetings === []) {
                $findings[] = Finding::create(
                    code: FindingCode::SectionWithoutSchedule,
                    message: "La sección {$section->displayLabel()} no tiene sesiones semanales asignadas.",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                );

                continue;
            }

            if ($section->delivery->isOnline()) {
                continue;
            }

            $missingClassroom = false;

            foreach ($section->meetings as $meeting) {
                if ($meeting->classroomId === null) {
                    $missingClassroom = true;
                    break;
                }
            }

            if ($missingClassroom) {
                $findings[] = Finding::create(
                    code: FindingCode::SectionWithoutSchedule,
                    message: "La sección presencial {$section->displayLabel()} tiene sesiones sin aula asignada.",
                    sectionIds: [$section->id],
                    entity: new FindingEntity('section', $section->id, $section->displayLabel()),
                );
            }
        }

        return $findings;
    }
}
