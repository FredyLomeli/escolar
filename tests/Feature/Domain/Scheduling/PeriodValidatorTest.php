<?php

use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\PeriodValidator;
use App\Enums\AcademicPeriodStatus;
use App\Enums\AvailabilityType;
use App\Enums\DeliveryType;
use App\Enums\TeacherOrigin;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\CurriculumTermRule;
use App\Models\GroupTerm;
use App\Models\Modality;
use App\Models\Program;
use App\Models\ReservedBlock;
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use Database\Seeders\SettingsSeeder;

test('PeriodValidator builds context from database, respects settings, historical teacher eligibility, and period-scoped availability', function () {
    $this->seed(SettingsSeeder::class);

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ADME', 'modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);

    ShiftWindow::factory()->create([
        'shift_id' => $shift->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '14:00:00',
    ]);

    ReservedBlock::factory()->create([
        'shift_id' => $shift->id,
        'name' => 'Inglés',
        'day_of_week' => 1,
        'starts_at' => '12:30:00',
        'ends_at' => '14:00:00',
    ]);

    $course = Course::factory()->create(['subject_code' => 'ADME', 'course_code' => 'ADM11']);
    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $course->id,
        'term_number' => 1,
        'weekly_hours' => 2.0,
    ]);
    CurriculumTermRule::factory()->create([
        'curriculum_id' => $curriculum->id,
        'term_number' => 1,
        'expected_courses' => 1,
    ]);

    $teacher = Teacher::factory()->create([
        'origin' => TeacherOrigin::Campus,
        'max_weekly_hours' => 20,
    ]);

    // Disponibilidad general lunes 10-12, pero específica del periodo lunes 08-10 (debe prevalecer la específica)
    TeacherAvailability::factory()->create([
        'teacher_id' => $teacher->id,
        'academic_period_id' => null,
        'day_of_week' => 1,
        'starts_at' => '10:00:00',
        'ends_at' => '12:00:00',
        'type' => AvailabilityType::Available,
    ]);

    $pastPeriod = AcademicPeriod::factory()->create([
        'code' => '202620',
        'year' => 2026,
        'term_of_year' => 1,
        'status' => AcademicPeriodStatus::Closed,
    ]);

    // Elegible por historial en periodo cerrado (sin teacher_courses manual)
    Section::factory()->create([
        'academic_period_id' => $pastPeriod->id,
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);

    $currentPeriod = AcademicPeriod::factory()->create([
        'code' => '202640',
        'year' => 2026,
        'term_of_year' => 2,
        'status' => AcademicPeriodStatus::Draft,
    ]);

    TeacherAvailability::factory()->create([
        'teacher_id' => $teacher->id,
        'academic_period_id' => $currentPeriod->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'type' => AvailabilityType::Available,
    ]);

    $classroom = Classroom::factory()->create(['code' => '101', 'capacity' => 25]);
    $group = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $currentPeriod->id,
    ]);
    $groupTerm = GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $currentPeriod->id,
        'term_number' => 1,
        'student_count' => 20,
    ]);

    $section = Section::factory()->create([
        'academic_period_id' => $currentPeriod->id,
        'course_id' => $course->id,
        'crn' => '10501',
        'teacher_id' => $teacher->id,
        'delivery' => DeliveryType::InPerson,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create([
        'section_id' => $section->id,
        'group_term_id' => $groupTerm->id,
    ]);
    SectionMeeting::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $classroom->id,
    ]);

    $validator = app(PeriodValidator::class);
    $report = $validator->validate($currentPeriod);

    expect($report->findings)->toBeEmpty()
        ->and($report->canPublish())->toBeTrue();

    // Si ahora cambiamos el mínimo suave de tamaño a 25 en settings, debe disparar SECTION_SIZE_LOW y respetar allow_publish_with_warnings
    Setting::setValue('section_size_soft_min', 25);
    $reportWithWarning = $validator->validate($currentPeriod->fresh());

    expect($reportWithWarning->warnings())->toHaveCount(1)
        ->and($reportWithWarning->warnings()[0]->code)->toBe(FindingCode::SectionSizeLow)
        ->and($reportWithWarning->canPublish(allowPublishWithWarnings: true))->toBeTrue()
        ->and($reportWithWarning->canPublish(allowPublishWithWarnings: false))->toBeFalse();
});

test('PeriodValidator blocks publishing when errors exist and filters findings per section', function () {
    $this->seed(SettingsSeeder::class);

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);

    $c1 = Course::factory()->create();
    $c2 = Course::factory()->create();
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c1->id, 'term_number' => 1]);
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c2->id, 'term_number' => 1]);

    $period = AcademicPeriod::factory()->create(['status' => AcademicPeriodStatus::Draft]);
    $classroom = Classroom::factory()->create(['capacity' => 15]);
    $teacher = Teacher::factory()->create();

    $group = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $period->id,
    ]);
    $groupTerm = GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $period->id,
        'term_number' => 1,
        'student_count' => 22,
    ]);

    $s1 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c1->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s1->id, 'group_term_id' => $groupTerm->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s1->id,
        'day_of_week' => 2,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $classroom->id,
    ]);

    $s2 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c2->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s2->id, 'group_term_id' => $groupTerm->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s2->id,
        'day_of_week' => 2,
        'starts_at' => '09:00:00',
        'ends_at' => '11:00:00',
        'classroom_id' => $classroom->id,
    ]);

    $report = app(PeriodValidator::class)->validate($period);

    expect($report->hasErrors())->toBeTrue()
        ->and($report->canPublish())->toBeFalse()
        ->and($report->counts()['errors'])->toBeGreaterThanOrEqual(5) // teacher, classroom, group conflicts + 2x capacity exceeded
        ->and($report->forSection($s1->id)->errors())->not->toBeEmpty();
});
