<?php

namespace App\Domain\Scheduling;

enum FindingCode: string
{
    case TeacherConflict = 'TEACHER_CONFLICT';
    case ClassroomConflict = 'CLASSROOM_CONFLICT';
    case GroupConflict = 'GROUP_CONFLICT';
    case CapacityExceeded = 'CAPACITY_EXCEEDED';
    case ReservedBlockConflict = 'RESERVED_BLOCK_CONFLICT';
    case TeacherUnavailable = 'TEACHER_UNAVAILABLE';
    case DuplicateCrn = 'DUPLICATE_CRN';
    case CompactionInvalid = 'COMPACTION_INVALID';

    case OutsideShiftWindow = 'OUTSIDE_SHIFT_WINDOW';
    case NoAvailabilityData = 'NO_AVAILABILITY_DATA';
    case TeacherNotEligible = 'TEACHER_NOT_ELIGIBLE';
    case TeacherOverload = 'TEACHER_OVERLOAD';
    case SectionSizeLow = 'SECTION_SIZE_LOW';
    case SectionSizeHigh = 'SECTION_SIZE_HIGH';
    case GroupCourseCount = 'GROUP_COURSE_COUNT';
    case SectionWithoutTeacher = 'SECTION_WITHOUT_TEACHER';
    case SectionWithoutSchedule = 'SECTION_WITHOUT_SCHEDULE';
    case HoursMismatch = 'HOURS_MISMATCH';

    case OnlineNoTeacher = 'ONLINE_NO_TEACHER';
    case CompactionCandidate = 'COMPACTION_CANDIDATE';

    public function defaultSeverity(): FindingSeverity
    {
        return match ($this) {
            self::TeacherConflict,
            self::ClassroomConflict,
            self::GroupConflict,
            self::CapacityExceeded,
            self::ReservedBlockConflict,
            self::TeacherUnavailable,
            self::DuplicateCrn,
            self::CompactionInvalid => FindingSeverity::Error,

            self::OutsideShiftWindow,
            self::NoAvailabilityData,
            self::TeacherNotEligible,
            self::TeacherOverload,
            self::SectionSizeLow,
            self::SectionSizeHigh,
            self::GroupCourseCount,
            self::SectionWithoutTeacher,
            self::SectionWithoutSchedule,
            self::HoursMismatch => FindingSeverity::Warning,

            self::OnlineNoTeacher,
            self::CompactionCandidate => FindingSeverity::Info,
        };
    }
}
