<?php

use App\Domain\Scheduling\FindingCode;
use App\Enums\AcademicPeriodStatus;
use App\Enums\AvailabilityType;
use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
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
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherCourse;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access period findings or publish endpoints', function () {
    $period = AcademicPeriod::factory()->create();

    $this->get(route('periods.findings', $period))->assertRedirect(route('login'));
    $this->getJson(route('periods.findings', $period))->assertUnauthorized();
    $this->post(route('periods.publish', $period))->assertRedirect(route('login'));
    $this->postJson(route('periods.publish', $period))->assertUnauthorized();
});

test('findings endpoint returns period findings and filters by section_id and severity', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);
    ShiftWindow::factory()->create([
        'shift_id' => $shift->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '14:00:00',
    ]);

    $c1 = Course::factory()->create();
    $c2 = Course::factory()->create();
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c1->id, 'term_number' => 1, 'weekly_hours' => 2.0]);
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c2->id, 'term_number' => 1, 'weekly_hours' => 2.0]);
    CurriculumTermRule::factory()->create(['curriculum_id' => $curriculum->id, 'term_number' => 1, 'expected_courses' => 2]);

    $period = AcademicPeriod::factory()->create(['status' => AcademicPeriodStatus::Draft]);
    $classroom1 = Classroom::factory()->create(['capacity' => 25]);
    $classroom2 = Classroom::factory()->create(['capacity' => 25]);
    $teacher = Teacher::factory()->create(['origin' => TeacherOrigin::Campus, 'max_weekly_hours' => 20]);
    TeacherCourse::factory()->create(['teacher_id' => $teacher->id, 'course_id' => $c1->id]);
    TeacherCourse::factory()->create(['teacher_id' => $teacher->id, 'course_id' => $c2->id]);
    TeacherAvailability::factory()->create([
        'teacher_id' => $teacher->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '14:00:00',
        'type' => AvailabilityType::Available,
    ]);

    $groupA = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $period->id,
    ]);
    $groupB = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $period->id,
    ]);

    $gtA = GroupTerm::factory()->create(['student_group_id' => $groupA->id, 'academic_period_id' => $period->id, 'term_number' => 1, 'student_count' => 20]);
    $gtB = GroupTerm::factory()->create(['student_group_id' => $groupB->id, 'academic_period_id' => $period->id, 'term_number' => 1, 'student_count' => 20]);

    // S1 y S2 chocan en docente el lunes 08:00-10:00
    $s1 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c1->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s1->id, 'group_term_id' => $gtA->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s1->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $classroom1->id,
    ]);

    $s2 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c2->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s2->id, 'group_term_id' => $gtB->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s2->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $classroom2->id,
    ]);

    // S3 sin docente presencial (solo advertencia)
    $s3 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c2->id,
        'teacher_id' => null,
        'delivery' => DeliveryType::InPerson,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s3->id, 'group_term_id' => $gtA->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s3->id,
        'day_of_week' => 1,
        'starts_at' => '10:00:00',
        'ends_at' => '12:00:00',
        'classroom_id' => $classroom1->id,
    ]);

    $this->actingAs($user)
        ->getJson(route('periods.findings', $period))
        ->assertOk()
        ->assertJsonPath('period.id', $period->id)
        ->assertJsonPath('counts.errors', 1)
        ->assertJsonPath('can_publish', false);

    $this->actingAs($user)
        ->getJson(route('periods.findings', ['academicPeriod' => $period->id, 'section_id' => $s3->id, 'severity' => 'warning']))
        ->assertOk()
        ->assertJsonPath('affected_section_ids', [$s3->id])
        ->assertJsonPath('affected_findings.0.code', FindingCode::SectionWithoutTeacher->value);
});

test('publishing a period fails with clear message when errors exist and succeeds once resolved', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);
    ShiftWindow::factory()->create([
        'shift_id' => $shift->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '14:00:00',
    ]);

    $course = Course::factory()->create();
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $course->id, 'term_number' => 1, 'weekly_hours' => 2.0]);
    CurriculumTermRule::factory()->create(['curriculum_id' => $curriculum->id, 'term_number' => 1, 'expected_courses' => 1]);

    $period = AcademicPeriod::factory()->create(['code' => '202660', 'status' => AcademicPeriodStatus::Draft]);
    $smallClassroom = Classroom::factory()->create(['capacity' => 12]);
    $largeClassroom = Classroom::factory()->create(['capacity' => 30]);
    $teacher = Teacher::factory()->create(['origin' => TeacherOrigin::Campus, 'max_weekly_hours' => 20]);
    TeacherCourse::factory()->create(['teacher_id' => $teacher->id, 'course_id' => $course->id]);
    TeacherAvailability::factory()->create([
        'teacher_id' => $teacher->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '14:00:00',
        'type' => AvailabilityType::Available,
    ]);

    $group = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $period->id,
    ]);
    $gt = GroupTerm::factory()->create(['student_group_id' => $group->id, 'academic_period_id' => $period->id, 'term_number' => 1, 'student_count' => 20]);

    $section = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $section->id, 'group_term_id' => $gt->id]);
    $meeting = SectionMeeting::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $smallClassroom->id, // 20 alumnos > cap 12 -> CAPACITY_EXCEEDED
    ]);

    // Intento vía Inertia / web falla con flash error claro
    $this->actingAs($user)
        ->from(route('dashboard', ['period_id' => $period->id]))
        ->post(route('periods.publish', $period))
        ->assertRedirect(route('dashboard', ['period_id' => $period->id]))
        ->assertSessionHas('error', 'No se puede publicar el periodo 202660: existen 1 error(es) que deben resolverse primero.');

    expect($period->fresh()?->status)->toBe(AcademicPeriodStatus::Draft);

    // Intento vía JSON falla con 422 y detalle
    $this->actingAs($user)
        ->postJson(route('periods.publish', $period))
        ->assertStatus(422)
        ->assertJsonPath('can_publish', false)
        ->assertJsonPath('errors.0.code', FindingCode::CapacityExceeded->value);

    // Corregir aula -> ya no hay errores -> publicar funciona
    $meeting->update(['classroom_id' => $largeClassroom->id]);

    $this->actingAs($user)
        ->from(route('dashboard', ['period_id' => $period->id]))
        ->post(route('periods.publish', $period))
        ->assertRedirect(route('dashboard', ['period_id' => $period->id]))
        ->assertSessionHas('success', 'Periodo 202660 publicado correctamente.');

    expect($period->fresh()?->status)->toBe(AcademicPeriodStatus::Published);
});

test('publish respects allow_publish_with_warnings setting and rejects closed periods', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create(['code' => '202660', 'status' => AcademicPeriodStatus::Draft]);
    $shift = Shift::factory()->create();

    // Sección presencial sin docente ni horario (solo advertencias, 0 errores)
    Section::factory()->create([
        'academic_period_id' => $period->id,
        'shift_id' => $shift->id,
        'delivery' => DeliveryType::InPerson,
        'teacher_id' => null,
    ]);

    Setting::setValue('allow_publish_with_warnings', false);

    $this->actingAs($user)
        ->postJson(route('periods.publish', $period))
        ->assertStatus(422)
        ->assertJsonPath('can_publish', false);

    expect($period->fresh()?->status)->toBe(AcademicPeriodStatus::Draft);

    Setting::setValue('allow_publish_with_warnings', true);

    $this->actingAs($user)
        ->postJson(route('periods.publish', $period))
        ->assertOk()
        ->assertJsonPath('period.status', AcademicPeriodStatus::Published->value);

    $closedPeriod = AcademicPeriod::factory()->create(['code' => '202520', 'status' => AcademicPeriodStatus::Closed]);

    $this->actingAs($user)
        ->postJson(route('periods.publish', $closedPeriod))
        ->assertStatus(422);
});

test('saving a section in draft is allowed even with conflicts and json endpoint returns affected findings', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create(['weeks' => 15, 'status' => AcademicPeriodStatus::Draft]);
    $shift = Shift::factory()->create();
    $classroom = Classroom::factory()->create(['capacity' => 30]);
    $teacher = Teacher::factory()->create();
    $c1 = Course::factory()->create();
    $c2 = Course::factory()->create();
    $group1 = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);
    $group2 = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);
    $gt1 = GroupTerm::factory()->create(['student_group_id' => $group1->id, 'academic_period_id' => $period->id, 'student_count' => 20]);
    $gt2 = GroupTerm::factory()->create(['student_group_id' => $group2->id, 'academic_period_id' => $period->id, 'student_count' => 20]);

    $s1 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c1->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s1->id, 'group_term_id' => $gt1->id]);
    SectionMeeting::factory()->create([
        'section_id' => $s1->id,
        'day_of_week' => 1,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
        'classroom_id' => $classroom->id,
    ]);

    $s2 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $c2->id,
        'teacher_id' => null,
        'shift_id' => $shift->id,
        'start_week' => 1,
        'end_week' => 14,
    ]);
    SectionGroup::factory()->create(['section_id' => $s2->id, 'group_term_id' => $gt2->id]);

    // Actualizar s2 provocando choque de docente y aula con s1 debe permitirse en draft y devolver hallazgos afectados
    $this->actingAs($user)
        ->putJson(route('sections.update', $s2), [
            'crn' => null,
            'teacher_id' => $teacher->id,
            'delivery' => DeliveryType::InPerson->value,
            'shift_id' => $shift->id,
            'start_week' => 1,
            'end_week' => 14,
            'students_override' => null,
            'status' => SectionStatus::Draft->value,
            'notes' => null,
            'meetings' => [
                [
                    'day_of_week' => 1,
                    'starts_at' => '08:00',
                    'ends_at' => '10:00',
                    'classroom_id' => $classroom->id,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('section_id', $s2->id)
        ->assertJsonPath('can_publish', false)
        ->assertJsonPath('counts.errors', 2);

    // Dashboard y Sections/Index comparten conteos y filtros de errores
    $this->actingAs($user)
        ->get(route('dashboard', ['period_id' => $period->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.errors_count', 2)
            ->where('stats.can_publish', false)
            ->has('alerts')
        );

    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id, 'pending' => 'with_errors']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('validationSummary.counts.errors', 2)
            ->where('validationSummary.can_publish', false)
            ->has('sections', 2)
        );
});
