<?php

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\SectionMeeting;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\Teacher;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access section management endpoints', function () {
    $section = Section::factory()->create();

    $this->get(route('sections.index'))->assertRedirect(route('login'));
    $this->post(route('sections.store'), [])->assertRedirect(route('login'));
    $this->put(route('sections.update', $section), [])->assertRedirect(route('login'));
    $this->delete(route('sections.destroy', $section))->assertRedirect(route('login'));
});

test('authenticated users can list and filter sections without lazy loading errors', function () {
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create([
        'code' => '202660',
        'weeks' => 15,
        'status' => AcademicPeriodStatus::Draft,
    ]);
    $shift = Shift::factory()->create();
    $teacher = Teacher::factory()->create(['full_name' => 'Dra. Laura Méndez']);
    $classroom = Classroom::factory()->create(['code' => 'A-101', 'capacity' => 35]);

    $groupA = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id, 'label' => 'ADME-M1']);
    $groupB = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id, 'label' => 'EJAD-S1']);

    $termA = GroupTerm::factory()->create(['student_group_id' => $groupA->id, 'academic_period_id' => $period->id, 'student_count' => 24]);
    $termB = GroupTerm::factory()->create(['student_group_id' => $groupB->id, 'academic_period_id' => $period->id, 'student_count' => 18]);

    $courseInPersonWithTeacher = Course::factory()->create(['subject_code' => 'ADM', 'course_code' => '101', 'name' => 'Administración I']);
    $courseInPersonPending = Course::factory()->create(['subject_code' => 'CON', 'course_code' => '102', 'name' => 'Contabilidad II']);
    $courseOnline = Course::factory()->create(['subject_code' => 'DER', 'course_code' => '201', 'name' => '@ Derecho Corporativo']);

    $section1 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $courseInPersonWithTeacher->id,
        'teacher_id' => $teacher->id,
        'delivery' => DeliveryType::InPerson,
        'shift_id' => $shift->id,
        'crn' => '11001',
    ]);
    $section1->groupTerms()->attach($termA->id);
    SectionMeeting::factory()->create([
        'section_id' => $section1->id,
        'day_of_week' => 1,
        'starts_at' => '07:00',
        'ends_at' => '09:00',
        'classroom_id' => $classroom->id,
    ]);

    $section2 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $courseInPersonPending->id,
        'teacher_id' => null,
        'delivery' => DeliveryType::InPerson,
        'shift_id' => $shift->id,
        'crn' => '11002',
    ]);
    $section2->groupTerms()->attach($termB->id);

    $section3 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $courseOnline->id,
        'teacher_id' => null,
        'delivery' => DeliveryType::OnlineAsync,
        'shift_id' => $shift->id,
        'crn' => '11003',
    ]);
    $section3->groupTerms()->attach($termA->id);

    // Listado completo
    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sections/Index')
            ->where('selectedPeriod.id', $period->id)
            ->has('sections', 3)
        );

    // Filtro por grupo
    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id, 'group_term_id' => $termB->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections', 1)
            ->where('sections.0.id', $section2->id)
        );

    // Filtro por búsqueda de CRN o materia
    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id, 'search' => '11001']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections', 1)
            ->where('sections.0.id', $section1->id)
        );

    // Filtro sin docente (solo presenciales, no cuenta online)
    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id, 'pending' => 'without_teacher']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections', 1)
            ->where('sections.0.id', $section2->id)
        );

    // Filtro sin horario o aula
    $this->actingAs($user)
        ->get(route('sections.index', ['period_id' => $period->id, 'pending' => 'without_schedule']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections', 2)
        );
});

test('coordinator can store a manual section with meetings', function () {
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create(['code' => '202660', 'weeks' => 15]);
    $shift = Shift::factory()->create();
    $group = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);
    $groupTerm = GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $period->id,
        'student_count' => 22,
    ]);
    $course = Course::factory()->create(['subject_code' => 'MER', 'course_code' => '305']);
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create();

    $this->actingAs($user)
        ->from(route('sections.index', ['period_id' => $period->id]))
        ->post(route('sections.store'), [
            'academic_period_id' => $period->id,
            'group_term_id' => $groupTerm->id,
            'course_id' => $course->id,
            'crn' => '55012',
            'teacher_id' => $teacher->id,
            'delivery' => DeliveryType::InPerson->value,
            'shift_id' => $shift->id,
            'start_week' => 1,
            'end_week' => 15,
            'students_override' => null,
            'status' => SectionStatus::Confirmed->value,
            'notes' => 'Clase agregada manualmente.',
            'meetings' => [
                [
                    'day_of_week' => 2,
                    'starts_at' => '09:00',
                    'ends_at' => '11:00',
                    'classroom_id' => $classroom->id,
                ],
            ],
        ])
        ->assertRedirect(route('sections.index', ['period_id' => $period->id]))
        ->assertSessionHas('success');

    $section = Section::query()
        ->where('academic_period_id', $period->id)
        ->where('crn', '55012')
        ->first();

    expect($section)
        ->not->toBeNull()
        ->teacher_id->toBe($teacher->id)
        ->status->toBe(SectionStatus::Confirmed)
        ->and($section->groupTerms()->pluck('group_terms.id')->all())->toBe([$groupTerm->id])
        ->and($section->meetings()->count())->toBe(1);
});

test('coordinator can update section attributes, clear teacher for online courses, sync meetings, and validate rules', function () {
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create(['code' => '202660', 'weeks' => 15]);
    $shiftA = Shift::factory()->create();
    $shiftB = Shift::factory()->create();
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create();

    $existingWithCrn = Section::factory()->create([
        'academic_period_id' => $period->id,
        'shift_id' => $shiftA->id,
        'crn' => '99001',
    ]);

    $section = Section::factory()->create([
        'academic_period_id' => $period->id,
        'shift_id' => $shiftA->id,
        'teacher_id' => $teacher->id,
        'delivery' => DeliveryType::InPerson,
        'start_week' => 1,
        'end_week' => 15,
        'crn' => '99002',
    ]);
    SectionMeeting::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => 1,
        'starts_at' => '07:00',
        'ends_at' => '09:00',
        'classroom_id' => null,
    ]);

    // Falla si end_week excede semanas del periodo, si CRN ya existe o si hora fin <= inicio
    $this->actingAs($user)
        ->put(route('sections.update', $section), [
            'crn' => $existingWithCrn->crn,
            'teacher_id' => null,
            'delivery' => DeliveryType::OnlineLive->value,
            'shift_id' => $shiftB->id,
            'start_week' => 1,
            'end_week' => 16,
            'students_override' => 29,
            'status' => SectionStatus::Draft->value,
            'notes' => null,
            'meetings' => [
                [
                    'day_of_week' => 3,
                    'starts_at' => '12:00',
                    'ends_at' => '11:00',
                    'classroom_id' => $classroom->id,
                ],
            ],
        ])
        ->assertSessionHasErrors(['crn', 'end_week', 'meetings.0.ends_at']);

    // Actualización válida (incluye dejar teacher_id null en entrega VV y reemplazar sesiones)
    $this->actingAs($user)
        ->from(route('sections.index', ['period_id' => $period->id]))
        ->put(route('sections.update', $section), [
            'crn' => '99002',
            'teacher_id' => null,
            'delivery' => DeliveryType::OnlineLive->value,
            'shift_id' => $shiftB->id,
            'start_week' => 6,
            'end_week' => 10,
            'students_override' => 29,
            'status' => SectionStatus::Confirmed->value,
            'notes' => 'Módulo 2 en línea.',
            'meetings' => [
                [
                    'day_of_week' => 6,
                    'starts_at' => '08:00:00',
                    'ends_at' => '12:00:00',
                    'classroom_id' => $classroom->id,
                ],
            ],
        ])
        ->assertRedirect(route('sections.index', ['period_id' => $period->id]))
        ->assertSessionHas('success');

    $fresh = $section->fresh(['meetings']);

    expect($fresh)
        ->teacher_id->toBeNull()
        ->delivery->toBe(DeliveryType::OnlineLive)
        ->shift_id->toBe($shiftB->id)
        ->start_week->toBe(6)
        ->end_week->toBe(10)
        ->students_override->toBe(29)
        ->status->toBe(SectionStatus::Confirmed)
        ->notes->toBe('Módulo 2 en línea.');

    expect($fresh->meetings)->toHaveCount(1);
    expect($fresh->meetings->first())
        ->day_of_week->toBe(6)
        ->classroom_id->toBe($classroom->id);
});

test('coordinator can delete a section', function () {
    $user = User::factory()->coordinator()->create();
    $period = AcademicPeriod::factory()->create();
    $section = Section::factory()->create(['academic_period_id' => $period->id]);

    $this->actingAs($user)
        ->from(route('sections.index', ['period_id' => $period->id]))
        ->delete(route('sections.destroy', $section))
        ->assertRedirect(route('sections.index', ['period_id' => $period->id]))
        ->assertSessionHas('success');

    expect(Section::query()->find($section->id))->toBeNull();
});
