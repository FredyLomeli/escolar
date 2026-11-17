<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\TimeRange;
use App\Enums\DeliveryType;
use App\Enums\TeacherOrigin;

final class TeacherAvailabilityRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        $findings = [];
        /** @var array<int, list<int>> $noDataSectionsByTeacher */
        $noDataSectionsByTeacher = [];

        foreach ($context->sections as $section) {
            if ($section->teacherId === null) {
                continue;
            }

            // Las secciones en línea externas o asíncronas no se validan contra disponibilidad de sede.
            if (in_array($section->delivery, [DeliveryType::OnlineLive, DeliveryType::OnlineAsync], true)) {
                continue;
            }

            $teacher = $context->teachersById[$section->teacherId] ?? null;

            if ($teacher === null || $teacher->origin === TeacherOrigin::External) {
                continue;
            }

            if (! $teacher->hasAvailabilityData) {
                $noDataSectionsByTeacher[$teacher->id][] = $section->id;

                continue;
            }

            if ($section->meetings === []) {
                continue;
            }

            $teacherLabel = "{$teacher->fullName} ({$teacher->employeeId})";
            $hasAvailableDeclarations = $teacher->hasAvailableDeclarations();

            foreach ($section->meetings as $meeting) {
                $unavailableSlots = $teacher->unavailableSlotsByDay[$meeting->dayOfWeek] ?? [];
                $hitsUnavailable = false;

                foreach ($unavailableSlots as $slot) {
                    if ($meeting->timeRange->overlaps($slot)) {
                        $hitsUnavailable = true;
                        break;
                    }
                }

                $outsideAvailable = false;

                if (! $hitsUnavailable && $hasAvailableDeclarations) {
                    $availableSlots = $teacher->availableSlotsByDay[$meeting->dayOfWeek] ?? [];
                    $covered = false;

                    foreach ($availableSlots as $slot) {
                        if ($slot->contains($meeting->timeRange)) {
                            $covered = true;
                            break;
                        }
                    }

                    if (! $covered) {
                        $outsideAvailable = true;
                    }
                }

                if ($hitsUnavailable || $outsideAvailable) {
                    $day = TimeRange::dayLabel($meeting->dayOfWeek);
                    $reason = $hitsUnavailable
                        ? 'está marcada como no disponible'
                        : 'queda fuera de su disponibilidad declarada';

                    $findings[] = Finding::create(
                        code: FindingCode::TeacherUnavailable,
                        message: "La sesión de {$section->displayLabel()} el {$day} ({$meeting->timeRange->format()}) {$reason} para {$teacherLabel}.",
                        sectionIds: [$section->id],
                        entity: new FindingEntity('teacher', $teacher->id, $teacherLabel),
                    );
                }
            }
        }

        foreach ($noDataSectionsByTeacher as $teacherId => $sectionIds) {
            $teacher = $context->teachersById[$teacherId];
            $teacherLabel = "{$teacher->fullName} ({$teacher->employeeId})";

            $findings[] = Finding::create(
                code: FindingCode::NoAvailabilityData,
                message: "El docente {$teacherLabel} no tiene disponibilidad horaria registrada; no es posible validar sus horarios.",
                sectionIds: $sectionIds,
                entity: new FindingEntity('teacher', $teacher->id, $teacherLabel),
            );
        }

        return $findings;
    }
}
