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

final class TeacherConflictRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<int, list<MeetingSnapshot>> $meetingsByTeacher */
        $meetingsByTeacher = [];
        /** @var array<int, SectionSnapshot> $sectionsById */
        $sectionsById = [];

        foreach ($context->sections as $section) {
            $sectionsById[$section->id] = $section;

            if ($section->teacherId === null || $section->meetings === []) {
                continue;
            }

            foreach ($section->meetings as $meeting) {
                $meetingsByTeacher[$section->teacherId][] = $meeting;
            }
        }

        $findings = [];

        foreach ($meetingsByTeacher as $teacherId => $meetings) {
            $teacher = $context->teachersById[$teacherId] ?? null;
            $teacherLabel = $teacher !== null
                ? "{$teacher->fullName} ({$teacher->employeeId})"
                : "Docente #{$teacherId}";

            foreach (ConflictPairFinder::findConflictingSectionPairs($meetings) as $pair) {
                $sectionA = $sectionsById[$pair['sectionA']];
                $sectionB = $sectionsById[$pair['sectionB']];
                $day = TimeRange::dayLabel($pair['meetingA']->dayOfWeek);
                $time = $pair['meetingA']->timeRange->format();

                $findings[] = Finding::create(
                    code: FindingCode::TeacherConflict,
                    message: "El docente {$teacherLabel} tiene choque de horario el {$day} ({$time}) entre {$sectionA->displayLabel()} y {$sectionB->displayLabel()}.",
                    sectionIds: [$pair['sectionA'], $pair['sectionB']],
                    entity: new FindingEntity('teacher', $teacherId, $teacherLabel),
                );
            }
        }

        return $findings;
    }
}
