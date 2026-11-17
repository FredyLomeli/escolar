<?php

use App\Domain\Scheduling\Data\ClassroomSnapshot;
use App\Domain\Scheduling\Data\GroupTermSnapshot;
use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\Data\PeriodSnapshot;
use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\ReservedBlockSnapshot;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Data\ShiftSnapshot;
use App\Domain\Scheduling\Data\TeacherSnapshot;
use App\Domain\Scheduling\Data\ValidationSettings;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingSeverity;
use App\Domain\Scheduling\PeriodValidator;
use App\Domain\Scheduling\TimeRange;
use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\TeacherOrigin;

function makeBasePeriod(): PeriodSnapshot
{
    return new PeriodSnapshot(
        id: 1,
        code: '202660',
        name: 'Ago – Dic 2026',
        weeks: 15,
        status: AcademicPeriodStatus::Draft,
    );
}

function makeMeeting(
    int $id,
    int $sectionId,
    int $day,
    string $start,
    string $end,
    ?int $classroomId = 1,
    ?int $capacity = 25,
    int $startWeek = 1,
    int $endWeek = 14,
): MeetingSnapshot {
    return new MeetingSnapshot(
        id: $id,
        sectionId: $sectionId,
        dayOfWeek: $day,
        timeRange: TimeRange::fromTimeStrings($start, $end),
        classroomId: $classroomId,
        classroomCode: $classroomId !== null ? "A{$classroomId}" : null,
        classroomName: $classroomId !== null ? "Aula {$classroomId}" : null,
        classroomCapacity: $capacity,
        startWeek: $startWeek,
        endWeek: $endWeek,
    );
}

function makeGroupTerm(
    int $id,
    int $modalityId = 1,
    int $shiftId = 1,
    int $studentCount = 20,
    array $curriculumCourseIds = [100, 101],
    ?int $expectedCourses = null,
): GroupTermSnapshot {
    return new GroupTermSnapshot(
        id: $id,
        studentGroupId: $id,
        label: "GRP-{$id}",
        termNumber: 1,
        studentCount: $studentCount,
        programCode: 'ADME',
        modalityId: $modalityId,
        modalityName: $modalityId === 1 ? 'Escolarizado' : 'Ejecutivo',
        shiftId: $shiftId,
        shiftName: "Turno {$shiftId}",
        curriculumId: $modalityId,
        curriculumCourseIds: $curriculumCourseIds,
        weeklyHoursByCourse: [100 => 2.0, 101 => 2.0],
        expectedCourses: $expectedCourses,
    );
}

function makeCleanTeacher(int $id = 1, ?int $maxWeeklyHours = 20): TeacherSnapshot
{
    return new TeacherSnapshot(
        id: $id,
        employeeId: "0010000{$id}",
        fullName: "Docente {$id}",
        origin: TeacherOrigin::Campus,
        maxWeeklyHours: $maxWeeklyHours,
        eligibleCourseIds: [100, 101],
        hasAvailabilityData: true,
        availableSlotsByDay: [
            1 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            2 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            3 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            4 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            5 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            6 => [TimeRange::fromTimeStrings('08:00', '18:00')],
        ],
        unavailableSlotsByDay: [],
    );
}

function makeCleanShift(int $id = 1, int $modalityId = 1): ShiftSnapshot
{
    return new ShiftSnapshot(
        id: $id,
        modalityId: $modalityId,
        name: "Turno {$id}",
        windowsByDay: [
            1 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            2 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            3 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            4 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            5 => [TimeRange::fromTimeStrings('08:00', '14:00')],
            6 => [TimeRange::fromTimeStrings('08:00', '18:00')],
        ],
        reservedBlocksByDay: [
            1 => [new ReservedBlockSnapshot(1, 'Inglés', 1, TimeRange::fromTimeStrings('12:30', '14:00'))],
        ],
    );
}

test('clean scenario produces zero findings and allows publishing', function () {
    $group = makeGroupTerm(1, expectedCourses: 1);
    $section = new SectionSnapshot(
        id: 1,
        courseId: 100,
        courseCode: 'ADME ADM11',
        courseName: 'Administración',
        crn: '10001',
        delivery: DeliveryType::InPerson,
        shiftId: 1,
        startWeek: 1,
        endWeek: 14,
        studentCount: 20,
        teacherId: 1,
        groupTerms: [$group],
        meetings: [makeMeeting(1, 1, 1, '08:00', '10:00', classroomId: 1, capacity: 25)],
        expectedWeeklyHours: 2.0,
    );

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$section],
        groupTerms: [$group],
        teachersById: [1 => makeCleanTeacher(1)],
        shiftsById: [1 => makeCleanShift(1)],
        classrooms: [new ClassroomSnapshot(1, '101', 'Aula 101', 25)],
    ));

    expect($report->findings)->toBeEmpty()
        ->and($report->hasErrors())->toBeFalse()
        ->and($report->canPublish())->toBeTrue();
});

test('TEACHER_CONFLICT fires across different shifts and modalities, and ignores disjoint module weeks', function () {
    $g1 = makeGroupTerm(1, modalityId: 1, shiftId: 1);
    $g2 = makeGroupTerm(2, modalityId: 2, shiftId: 2);
    $g3 = makeGroupTerm(3, modalityId: 2, shiftId: 2);

    $teacher = new TeacherSnapshot(
        id: 1,
        employeeId: '00100001',
        fullName: 'Docente Multi',
        origin: TeacherOrigin::Campus,
        maxWeeklyHours: 20,
        eligibleCourseIds: [100, 101],
        hasAvailabilityData: true,
        availableSlotsByDay: [1 => [TimeRange::fromTimeStrings('08:00', '22:00')]],
    );

    // S1 (escolarizado matutino, sem 1-14) choca con S2 (ejecutivo, sem 1-5) el lunes 10:00-12:00
    $s1 = new SectionSnapshot(1, 100, 'ADME A', 'Materia A', '1', DeliveryType::InPerson, 1, 1, 14, 20, 1, [$g1], [makeMeeting(1, 1, 1, '10:00', '12:00', 1, 25, 1, 14)], 2.0);
    $s2 = new SectionSnapshot(2, 101, 'EJAD B', 'Materia B', '2', DeliveryType::InPerson, 2, 1, 5, 20, 1, [$g2], [makeMeeting(2, 2, 1, '11:00', '13:00', 2, 25, 1, 5)], 2.0);
    // S3 (ejecutivo módulo 2, sem 6-10) coincide en horario con S2 pero NO en semanas -> no choca con S2, aunque sí con S1 (sem 1-14)
    $s3 = new SectionSnapshot(3, 101, 'EJAD C', 'Materia C', '3', DeliveryType::InPerson, 2, 6, 10, 20, 1, [$g3], [makeMeeting(3, 3, 1, '11:00', '13:00', 3, 25, 6, 10)], 2.0);

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$s1, $s2, $s3],
        groupTerms: [$g1, $g2, $g3],
        teachersById: [1 => $teacher],
        shiftsById: [1 => makeCleanShift(1, 1), 2 => makeCleanShift(2, 2)],
    ));

    $conflicts = array_values(array_filter(
        $report->errors(),
        static fn ($f): bool => $f->code === FindingCode::TeacherConflict,
    ));

    // Solo (1,2) y (1,3) chocan; (2,3) en módulos 1 y 2 NO chocan
    expect($conflicts)->toHaveCount(2)
        ->and($conflicts[0]->sectionIds)->toBe([1, 2])
        ->and($conflicts[1]->sectionIds)->toBe([1, 3]);
});

test('compacted section counts teacher once and sums group students for classroom capacity', function () {
    $g1 = makeGroupTerm(1, studentCount: 14);
    $g2 = makeGroupTerm(2, studentCount: 14);

    // Compactada: 14 + 14 = 28 alumnos, aula capacidad 25 -> CAPACITY_EXCEEDED pero SIN TEACHER_CONFLICT
    $compacted = new SectionSnapshot(
        id: 1,
        courseId: 100,
        courseCode: 'TRCO C1',
        courseName: 'Tronco Común',
        crn: '10001',
        delivery: DeliveryType::InPerson,
        shiftId: 1,
        startWeek: 1,
        endWeek: 14,
        studentCount: 28,
        teacherId: 1,
        groupTerms: [$g1, $g2],
        meetings: [makeMeeting(1, 1, 2, '08:00', '10:00', classroomId: 1, capacity: 25)],
        expectedWeeklyHours: 2.0,
    );

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$compacted],
        groupTerms: [$g1, $g2],
        teachersById: [1 => makeCleanTeacher(1)],
        shiftsById: [1 => makeCleanShift(1)],
    ));

    $codes = array_map(static fn ($f): FindingCode => $f->code, $report->findings);

    expect($codes)->toContain(FindingCode::CapacityExceeded)
        ->and($codes)->not->toContain(FindingCode::TeacherConflict)
        ->and($codes)->not->toContain(FindingCode::CompactionInvalid);
});

test('CLASSROOM_CONFLICT, GROUP_CONFLICT, RESERVED_BLOCK_CONFLICT, DUPLICATE_CRN and COMPACTION_INVALID are detected', function () {
    $g1 = makeGroupTerm(1, modalityId: 1, shiftId: 1, studentCount: 15, curriculumCourseIds: [100]);
    $g2 = makeGroupTerm(2, modalityId: 2, shiftId: 1, studentCount: 15, curriculumCourseIds: [101]);

    $s1 = new SectionSnapshot(
        id: 1,
        courseId: 100,
        courseCode: 'ADME A',
        courseName: 'Materia A',
        crn: '99999',
        delivery: DeliveryType::InPerson,
        shiftId: 1,
        startWeek: 1,
        endWeek: 14,
        studentCount: 15,
        teacherId: 1,
        groupTerms: [$g1],
        meetings: [makeMeeting(1, 1, 1, '12:00', '13:30', classroomId: 1, capacity: 30)], // invade inglés 12:30-14:00
        expectedWeeklyHours: 1.5,
    );

    $s2 = new SectionSnapshot(
        id: 2,
        courseId: 100,
        courseCode: 'ADME B',
        courseName: 'Materia B',
        crn: '99999', // CRN duplicado
        delivery: DeliveryType::InPerson,
        shiftId: 1,
        startWeek: 1,
        endWeek: 14,
        studentCount: 30,
        teacherId: 2,
        groupTerms: [$g1, $g2], // mezcla modalidad 1 y 2 + curso 100 no está en plan de g2 + choca grupo g1 y aula 1 con s1
        meetings: [makeMeeting(2, 2, 1, '12:30', '13:30', classroomId: 1, capacity: 30)],
        expectedWeeklyHours: 1.0,
    );

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$s1, $s2],
        groupTerms: [$g1, $g2],
        teachersById: [1 => makeCleanTeacher(1), 2 => makeCleanTeacher(2)],
        shiftsById: [1 => makeCleanShift(1)],
    ));

    $errorCodes = array_map(static fn ($f): FindingCode => $f->code, $report->errors());

    expect($errorCodes)->toContain(FindingCode::ClassroomConflict)
        ->and($errorCodes)->toContain(FindingCode::GroupConflict)
        ->and($errorCodes)->toContain(FindingCode::ReservedBlockConflict)
        ->and($errorCodes)->toContain(FindingCode::DuplicateCrn)
        ->and($errorCodes)->toContain(FindingCode::CompactionInvalid);
});

test('TEACHER_UNAVAILABLE detects unavailable slots and sessions outside available slots, while NO_AVAILABILITY_DATA warns when empty', function () {
    $g1 = makeGroupTerm(1);

    $teacherWithSlots = new TeacherSnapshot(
        id: 1,
        employeeId: '001',
        fullName: 'Con Franjas',
        origin: TeacherOrigin::Campus,
        maxWeeklyHours: 20,
        eligibleCourseIds: [100],
        hasAvailabilityData: true,
        availableSlotsByDay: [1 => [TimeRange::fromTimeStrings('08:00', '11:00')]],
        unavailableSlotsByDay: [1 => [TimeRange::fromTimeStrings('09:00', '10:00')]],
    );

    $teacherNoData = new TeacherSnapshot(
        id: 2,
        employeeId: '002',
        fullName: 'Sin Datos',
        origin: TeacherOrigin::Campus,
        maxWeeklyHours: 20,
        eligibleCourseIds: [100],
        hasAvailabilityData: false,
    );

    // S1 cae en horario unavailable (09:00-10:00) y S2 sale de available (11:00-12:00)
    $s1 = new SectionSnapshot(1, 100, 'C1', 'Curso 1', '1', DeliveryType::InPerson, 1, 1, 14, 20, 1, [$g1], [
        makeMeeting(1, 1, 1, '09:00', '10:00', 1, 25),
        makeMeeting(2, 1, 1, '11:00', '12:00', 1, 25),
    ], 2.0);

    // S2 usa docente sin datos de disponibilidad
    $s2 = new SectionSnapshot(2, 100, 'C1', 'Curso 1', '2', DeliveryType::InPerson, 1, 1, 14, 20, 2, [], [
        makeMeeting(3, 2, 2, '08:00', '10:00', 2, 25),
    ]);

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$s1, $s2],
        groupTerms: [$g1],
        teachersById: [1 => $teacherWithSlots, 2 => $teacherNoData],
        shiftsById: [1 => makeCleanShift(1)],
    ));

    $unavailable = array_values(array_filter($report->errors(), static fn ($f): bool => $f->code === FindingCode::TeacherUnavailable));
    $noData = array_values(array_filter($report->warnings(), static fn ($f): bool => $f->code === FindingCode::NoAvailabilityData));

    expect($unavailable)->toHaveCount(2)
        ->and($noData)->toHaveCount(1)
        ->and($noData[0]->sectionIds)->toBe([2]);
});

test('all warning and info codes fire in their corresponding scenarios', function () {
    $gLow = makeGroupTerm(1, studentCount: 6, expectedCourses: 3);
    $gHigh = makeGroupTerm(2, studentCount: 35, expectedCourses: 1);

    // Docente sobrecargado (max 4 h, tiene 6 h en sem 1-14) y no elegible para curso 999
    $overloadedTeacher = new TeacherSnapshot(
        id: 1,
        employeeId: '001',
        fullName: 'Sobrecargado',
        origin: TeacherOrigin::Campus,
        maxWeeklyHours: 4,
        eligibleCourseIds: [100], // no tiene 999
        hasAvailabilityData: true,
        availableSlotsByDay: [
            2 => [TimeRange::fromTimeStrings('08:00', '21:00')],
            3 => [TimeRange::fromTimeStrings('08:00', '21:00')],
        ],
    );

    // S1: OUTSIDE_SHIFT_WINDOW (15:00-18:00 fuera de 08:00-14:00), TEACHER_NOT_ELIGIBLE (999), SECTION_SIZE_LOW (6 < 10), HOURS_MISMATCH (3h != 2h)
    $s1 = new SectionSnapshot(1, 999, 'EXT 999', 'Optativa', '1', DeliveryType::InPerson, 1, 1, 14, 6, 1, [$gLow], [
        makeMeeting(1, 1, 2, '15:00', '18:00', 1, 40),
    ], 2.0);

    // S2: mismo docente suma otras 3 h -> total 6 h > 4 h (TEACHER_OVERLOAD), SECTION_SIZE_HIGH (35 > 30)
    $s2 = new SectionSnapshot(2, 100, 'ADME 100', 'Admin', '2', DeliveryType::InPerson, 1, 1, 14, 35, 1, [$gHigh], [
        makeMeeting(2, 2, 3, '08:00', '11:00', 1, 40),
    ], 3.0);

    // S3: presencial sin docente ni sesiones -> SECTION_WITHOUT_TEACHER + SECTION_WITHOUT_SCHEDULE
    $s3 = new SectionSnapshot(3, 100, 'ADME 100', 'Admin', '3', DeliveryType::InPerson, 1, 1, 14, 20, null, [], []);

    // S4: en línea asíncrona sin docente ni sesiones -> ONLINE_NO_TEACHER (info), SIN SECTION_WITHOUT_TEACHER ni SECTION_WITHOUT_SCHEDULE
    $s4 = new SectionSnapshot(4, 100, 'ADME 100', 'Admin', '4', DeliveryType::OnlineAsync, 1, 1, 14, 20, null, [], [], 3.0);

    // S5 y S6: candidatas a compactación (misma materia, modalidad, semanas, horario; 10 + 10 = 20 <= aula 25)
    $gC1 = makeGroupTerm(3, studentCount: 10);
    $gC2 = makeGroupTerm(4, studentCount: 10);
    $s5 = new SectionSnapshot(5, 101, 'TRCO 101', 'Común', '5', DeliveryType::InPerson, 1, 1, 14, 10, null, [$gC1], [
        makeMeeting(3, 5, 4, '08:00', '10:00', 2, 25),
    ], 2.0);
    $s6 = new SectionSnapshot(6, 101, 'TRCO 101', 'Común', '6', DeliveryType::InPerson, 1, 1, 14, 10, null, [$gC2], [
        makeMeeting(4, 6, 4, '08:00', '10:00', 3, 25),
    ], 2.0);

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$s1, $s2, $s3, $s4, $s5, $s6],
        groupTerms: [$gLow, $gHigh, $gC1, $gC2],
        teachersById: [1 => $overloadedTeacher],
        shiftsById: [1 => makeCleanShift(1)],
        classrooms: [
            new ClassroomSnapshot(1, 'MAGNA', 'Aula Magna', 40),
            new ClassroomSnapshot(2, '101', 'Aula 101', 25),
        ],
    ));

    $codes = array_map(static fn ($f): FindingCode => $f->code, $report->findings);

    expect($codes)->toContain(FindingCode::OutsideShiftWindow)
        ->and($codes)->toContain(FindingCode::TeacherNotEligible)
        ->and($codes)->toContain(FindingCode::TeacherOverload)
        ->and($codes)->toContain(FindingCode::SectionSizeLow)
        ->and($codes)->toContain(FindingCode::SectionSizeHigh)
        ->and($codes)->toContain(FindingCode::GroupCourseCount)
        ->and($codes)->toContain(FindingCode::SectionWithoutTeacher)
        ->and($codes)->toContain(FindingCode::SectionWithoutSchedule)
        ->and($codes)->toContain(FindingCode::HoursMismatch)
        ->and($codes)->toContain(FindingCode::OnlineNoTeacher)
        ->and($codes)->toContain(FindingCode::CompactionCandidate);

    $s4Findings = $report->forSection(4)->findings;
    $s4Codes = array_map(static fn ($f): FindingCode => $f->code, $s4Findings);
    expect($s4Codes)->toBe([FindingCode::OnlineNoTeacher]);
});

test('modular teacher overload calculates peak weekly hours rather than summing disjoint modules', function () {
    $teacher = makeCleanTeacher(1, maxWeeklyHours: 8);

    // 6h en módulo 1 (sem 1-5) y 6h en módulo 2 (sem 6-10): pico semanal = 6h <= 8h -> sin sobrecarga
    $s1 = new SectionSnapshot(1, 100, 'C1', 'M1', '1', DeliveryType::InPerson, 1, 1, 5, 20, 1, [], [
        makeMeeting(1, 1, 6, '08:00', '14:00', 1, 25, 1, 5),
    ]);
    $s2 = new SectionSnapshot(2, 101, 'C2', 'M2', '2', DeliveryType::InPerson, 1, 6, 10, 20, 1, [], [
        makeMeeting(2, 2, 6, '08:00', '14:00', 1, 25, 6, 10),
    ]);

    $report = (new PeriodValidator)->validateContext(new PeriodValidationContext(
        period: makeBasePeriod(),
        settings: new ValidationSettings,
        sections: [$s1, $s2],
        teachersById: [1 => $teacher],
        shiftsById: [1 => makeCleanShift(1)],
    ));

    $overload = array_filter($report->findings, static fn ($f): bool => $f->code === FindingCode::TeacherOverload);
    expect($overload)->toBeEmpty()
        ->and(FindingCode::TeacherOverload->defaultSeverity())->toBe(FindingSeverity::Warning);
});
