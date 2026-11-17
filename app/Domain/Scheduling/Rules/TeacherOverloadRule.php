<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;

final class TeacherOverloadRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<int, list<SectionSnapshot>> $sectionsByTeacher */
        $sectionsByTeacher = [];

        foreach ($context->sections as $section) {
            if ($section->teacherId === null || $section->meetings === []) {
                continue;
            }

            $sectionsByTeacher[$section->teacherId][] = $section;
        }

        $findings = [];
        $totalWeeks = max(1, $context->period->weeks);

        foreach ($sectionsByTeacher as $teacherId => $sections) {
            $teacher = $context->teachersById[$teacherId] ?? null;

            if ($teacher === null || $teacher->maxWeeklyHours === null) {
                continue;
            }

            $peakMinutes = 0;

            for ($week = 1; $week <= $totalWeeks; $week++) {
                $weekMinutes = 0;

                foreach ($sections as $section) {
                    if ($section->startWeek <= $week && $week <= $section->endWeek) {
                        foreach ($section->meetings as $meeting) {
                            $weekMinutes += $meeting->timeRange->durationMinutes();
                        }
                    }
                }

                if ($weekMinutes > $peakMinutes) {
                    $peakMinutes = $weekMinutes;
                }
            }

            $peakHours = round($peakMinutes / 60.0, 2);

            if ($peakHours <= (float) $teacher->maxWeeklyHours) {
                continue;
            }

            $teacherLabel = "{$teacher->fullName} ({$teacher->employeeId})";
            $formattedHours = $this->formatHours($peakHours);
            $sectionIds = array_map(
                static fn (SectionSnapshot $section): int => $section->id,
                $sections,
            );

            $findings[] = Finding::create(
                code: FindingCode::TeacherOverload,
                message: "El docente {$teacherLabel} suma {$formattedHours} h/semana (límite configurado: {$teacher->maxWeeklyHours} h).",
                sectionIds: $sectionIds,
                entity: new FindingEntity('teacher', $teacher->id, $teacherLabel),
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
