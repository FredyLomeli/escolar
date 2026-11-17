<?php

use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\ScheduleOverlap;
use App\Domain\Scheduling\TimeRange;

test('weeksOverlap detects overlapping and disjoint week ranges', function () {
    expect(ScheduleOverlap::weeksOverlap(1, 14, 1, 14))->toBeTrue()
        ->and(ScheduleOverlap::weeksOverlap(1, 5, 5, 10))->toBeTrue()
        ->and(ScheduleOverlap::weeksOverlap(1, 5, 6, 10))->toBeFalse()
        ->and(ScheduleOverlap::weeksOverlap(11, 15, 6, 10))->toBeFalse();
});

test('timeOverlap detects overlapping intervals on same day and allows adjacent or different days', function () {
    $morning = TimeRange::fromTimeStrings('08:00', '10:00');
    $overlapping = TimeRange::fromTimeStrings('09:30:00', '11:30:00');
    $adjacent = TimeRange::fromTimeStrings('10:00', '12:00');

    expect(ScheduleOverlap::timeOverlap(1, $morning, 1, $overlapping))->toBeTrue()
        ->and(ScheduleOverlap::timeOverlap(1, $morning, 1, $adjacent))->toBeFalse()
        ->and(ScheduleOverlap::timeOverlap(1, $morning, 2, $overlapping))->toBeFalse();
});

test('meetingsConflict requires both week overlap and time overlap', function () {
    $mod1 = new MeetingSnapshot(
        id: 1,
        sectionId: 10,
        dayOfWeek: 1,
        timeRange: TimeRange::fromTimeStrings('19:00', '22:00'),
        classroomId: 1,
        classroomCode: '101',
        classroomName: 'Aula 101',
        classroomCapacity: 25,
        startWeek: 1,
        endWeek: 5,
    );

    $mod2SameTime = new MeetingSnapshot(
        id: 2,
        sectionId: 11,
        dayOfWeek: 1,
        timeRange: TimeRange::fromTimeStrings('19:00', '22:00'),
        classroomId: 1,
        classroomCode: '101',
        classroomName: 'Aula 101',
        classroomCapacity: 25,
        startWeek: 6,
        endWeek: 10,
    );

    $regularOverlapping = new MeetingSnapshot(
        id: 3,
        sectionId: 12,
        dayOfWeek: 1,
        timeRange: TimeRange::fromTimeStrings('20:00', '21:00'),
        classroomId: 1,
        classroomCode: '101',
        classroomName: 'Aula 101',
        classroomCapacity: 25,
        startWeek: 1,
        endWeek: 14,
    );

    expect(ScheduleOverlap::meetingsConflict($mod1, $mod2SameTime))->toBeFalse()
        ->and(ScheduleOverlap::meetingsConflict($mod1, $regularOverlapping))->toBeTrue();
});

test('TimeRange::mergeIntervals merges adjacent and overlapping intervals', function () {
    $merged = TimeRange::mergeIntervals([
        TimeRange::fromTimeStrings('10:00', '12:00'),
        TimeRange::fromTimeStrings('08:00', '10:00'),
        TimeRange::fromTimeStrings('14:00', '16:00'),
        TimeRange::fromTimeStrings('15:00', '17:00'),
    ]);

    expect($merged)->toHaveCount(2)
        ->and($merged[0]->format())->toBe('08:00–12:00')
        ->and($merged[1]->format())->toBe('14:00–17:00')
        ->and($merged[0]->contains(TimeRange::fromTimeStrings('09:00', '11:00')))->toBeTrue();
});
