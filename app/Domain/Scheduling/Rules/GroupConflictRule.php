<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\GroupTermSnapshot;
use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\Rules\Support\ConflictPairFinder;
use App\Domain\Scheduling\TimeRange;

final class GroupConflictRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        /** @var array<int, list<MeetingSnapshot>> $meetingsByGroupTerm */
        $meetingsByGroupTerm = [];
        /** @var array<int, GroupTermSnapshot> $groupTermsById */
        $groupTermsById = [];
        /** @var array<int, SectionSnapshot> $sectionsById */
        $sectionsById = [];

        foreach ($context->sections as $section) {
            $sectionsById[$section->id] = $section;

            if ($section->meetings === []) {
                continue;
            }

            foreach ($section->groupTerms as $groupTerm) {
                $groupTermsById[$groupTerm->id] = $groupTerm;

                foreach ($section->meetings as $meeting) {
                    $meetingsByGroupTerm[$groupTerm->id][] = $meeting;
                }
            }
        }

        $findings = [];

        foreach ($meetingsByGroupTerm as $groupTermId => $meetings) {
            $groupTerm = $groupTermsById[$groupTermId];

            foreach (ConflictPairFinder::findConflictingSectionPairs($meetings, requireDistinctSections: true) as $pair) {
                $sectionA = $sectionsById[$pair['sectionA']];
                $sectionB = $sectionsById[$pair['sectionB']];
                $day = TimeRange::dayLabel($pair['meetingA']->dayOfWeek);
                $time = $pair['meetingA']->timeRange->format();

                $findings[] = Finding::create(
                    code: FindingCode::GroupConflict,
                    message: "El grupo {$groupTerm->label} tiene dos materias en el mismo horario el {$day} ({$time}): {$sectionA->displayLabel()} y {$sectionB->displayLabel()}.",
                    sectionIds: [$pair['sectionA'], $pair['sectionB']],
                    entity: new FindingEntity('group', $groupTermId, $groupTerm->label),
                );
            }
        }

        return $findings;
    }
}
