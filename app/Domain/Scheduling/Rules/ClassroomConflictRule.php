<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\Rules\Support\ConflictPairFinder;
use App\Domain\Scheduling\TimeRange;

final class ClassroomConflictRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<int, list<MeetingSnapshot>> $meetingsByClassroom */
        $meetingsByClassroom = [];
        /** @var array<int, SectionSnapshot> $sectionsById */
        $sectionsById = [];
        /** @var array<int, string> $classroomLabels */
        $classroomLabels = [];

        foreach ($context->sections as $section) {
            $sectionsById[$section->id] = $section;

            foreach ($section->meetings as $meeting) {
                if ($meeting->classroomId === null) {
                    continue;
                }

                $meetingsByClassroom[$meeting->classroomId][] = $meeting;
                $classroomLabels[$meeting->classroomId] ??= $meeting->classroomCode !== null
                    ? "{$meeting->classroomCode} — {$meeting->classroomName}"
                    : "Aula #{$meeting->classroomId}";
            }
        }

        $findings = [];

        foreach ($meetingsByClassroom as $classroomId => $meetings) {
            $classroomLabel = $classroomLabels[$classroomId];

            foreach (ConflictPairFinder::findConflictingSectionPairs($meetings) as $pair) {
                $sectionA = $sectionsById[$pair['sectionA']];
                $sectionB = $sectionsById[$pair['sectionB']];
                $day = TimeRange::dayLabel($pair['meetingA']->dayOfWeek);
                $time = $pair['meetingA']->timeRange->format();

                $findings[] = Finding::create(
                    code: FindingCode::ClassroomConflict,
                    message: "El aula {$classroomLabel} tiene dos clases empalmadas el {$day} ({$time}): {$sectionA->displayLabel()} y {$sectionB->displayLabel()}.",
                    sectionIds: [$pair['sectionA'], $pair['sectionB']],
                    entity: new FindingEntity('classroom', $classroomId, $classroomLabel),
                );
            }
        }

        return $findings;
    }
}
