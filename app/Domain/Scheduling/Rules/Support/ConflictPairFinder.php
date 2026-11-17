<?php

namespace App\Domain\Scheduling\Rules\Support;

use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\ScheduleOverlap;

final class ConflictPairFinder
{
    /**
     * Encuentra pares únicos de secciones en conflicto dentro de una lista de sesiones.
     *
     * @param  list<MeetingSnapshot>  $meetings
     * @return list<array{sectionA: int, sectionB: int, meetingA: MeetingSnapshot, meetingB: MeetingSnapshot}>
     */
    public static function findConflictingSectionPairs(
        array $meetings,
        bool $requireDistinctSections = false,
    ): array {
        $count = count($meetings);
        $pairs = [];
        $seen = [];

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $meetings[$i];
                $b = $meetings[$j];

                if ($requireDistinctSections && $a->sectionId === $b->sectionId) {
                    continue;
                }

                if ($a->id === $b->id) {
                    continue;
                }

                if (! ScheduleOverlap::meetingsConflict($a, $b)) {
                    continue;
                }

                $minSection = min($a->sectionId, $b->sectionId);
                $maxSection = max($a->sectionId, $b->sectionId);
                $key = "{$minSection}:{$maxSection}";

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $pairs[] = [
                    'sectionA' => $a->sectionId,
                    'sectionB' => $b->sectionId,
                    'meetingA' => $a,
                    'meetingB' => $b,
                ];
            }
        }

        return $pairs;
    }
}
