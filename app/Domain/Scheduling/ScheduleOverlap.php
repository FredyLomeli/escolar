<?php

namespace App\Domain\Scheduling;

use App\Domain\Scheduling\Data\MeetingSnapshot;

final class ScheduleOverlap
{
    public static function weeksOverlap(int $aStartWeek, int $aEndWeek, int $bStartWeek, int $bEndWeek): bool
    {
        return $aStartWeek <= $bEndWeek && $bStartWeek <= $aEndWeek;
    }

    public static function timeOverlap(int $aDayOfWeek, TimeRange $aTime, int $bDayOfWeek, TimeRange $bTime): bool
    {
        return $aDayOfWeek === $bDayOfWeek && $aTime->overlaps($bTime);
    }

    public static function meetingsConflict(MeetingSnapshot $a, MeetingSnapshot $b): bool
    {
        return self::weeksOverlap($a->startWeek, $a->endWeek, $b->startWeek, $b->endWeek)
            && self::timeOverlap($a->dayOfWeek, $a->timeRange, $b->dayOfWeek, $b->timeRange);
    }
}
