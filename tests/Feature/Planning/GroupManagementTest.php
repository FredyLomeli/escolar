<?php

use App\Enums\AcademicPeriodStatus;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\GroupTerm;
use App\Models\Modality;
use App\Models\Program;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access group management endpoints', function () {
    $this->get(route('groups.index'))->assertRedirect(route('login'));
    $this->post(route('groups.store'), [])->assertRedirect(route('login'));
    $this->patch(route('groups.bulk-update'), [])->assertRedirect(route('login'));
});

test('authenticated users can list and filter group terms by period, program, search, and confirmation', function () {
    $user = User::factory()->coordinator()->create();

    $regular = Modality::factory()->regular()->create();
    $executive = Modality::factory()->executive()->create();

    $programAdme = Program::factory()->create([
        'code' => 'ADME',
        'name' => 'Administración de Empresas',
        'modality_id' => $regular->id,
    ]);
    $programEjad = Program::factory()->create([
        'code' => 'EJAD',
        'name' => 'Administración Ejecutiva',
        'modality_id' => $executive->id,
    ]);

    $curriculumAdme = Curriculum::factory()->create(['program_id' => $programAdme->id]);
    $curriculumEjad = Curriculum::factory()->create(['program_id' => $programEjad->id]);

    $shiftMorning = Shift::factory()->create(['modality_id' => $regular->id, 'name' => 'Matutino']);
    $shiftSabatino = Shift::factory()->create(['modality_id' => $executive->id, 'name' => 'Sabatino']);

    $period = AcademicPeriod::factory()->create([
        'code' => '202660',
        'status' => AcademicPeriodStatus::Draft,
    ]);
    $otherPeriod = AcademicPeriod::factory()->create([
        'code' => '202640',
        'status' => AcademicPeriodStatus::Closed,
    ]);

    $groupAdme = StudentGroup::factory()->create([
        'program_id' => $programAdme->id,
        'curriculum_id' => $curriculumAdme->id,
        'shift_id' => $shiftMorning->id,
        'entry_period_id' => $period->id,
        'label' => 'ADME-2026-M',
        'status' => StudentGroupStatus::Active,
    ]);
    $groupEjad = StudentGroup::factory()->create([
        'program_id' => $programEjad->id,
        'curriculum_id' => $curriculumEjad->id,
        'shift_id' => $shiftSabatino->id,
        'entry_period_id' => $period->id,
        'label' => 'EJAD-2026-S',
        'status' => StudentGroupStatus::Active,
    ]);

    GroupTerm::factory()->create([
        'student_group_id' => $groupAdme->id,
        'academic_period_id' => $period->id,
        'term_number' => 1,
        'student_count' => 28,
        'student_count_confirmed' => true,
    ]);
    GroupTerm::factory()->create([
        'student_group_id' => $groupEjad->id,
        'academic_period_id' => $period->id,
        'term_number' => 4,
        'student_count' => 15,
        'student_count_confirmed' => false,
    ]);
    GroupTerm::factory()->create([
        'student_group_id' => $groupAdme->id,
        'academic_period_id' => $otherPeriod->id,
        'term_number' => 1,
        'student_count' => 20,
        'student_count_confirmed' => true,
    ]);

    // Listado base del periodo
    $this->actingAs($user)
        ->get(route('groups.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Index')
            ->where('selectedPeriod.id', $period->id)
            ->has('groupTerms', 2)
            ->has('programs', 2)
            ->has('shifts', 2)
        );

    // Filtro por búsqueda
    $this->actingAs($user)
        ->get(route('groups.index', ['period_id' => $period->id, 'search' => 'EJAD']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupTerms', 1)
            ->where('groupTerms.0.student_group.label', 'EJAD-2026-S')
        );

    // Filtro por carrera
    $this->actingAs($user)
        ->get(route('groups.index', ['period_id' => $period->id, 'program_id' => $programAdme->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupTerms', 1)
            ->where('groupTerms.0.student_group.program.code', 'ADME')
        );

    // Filtro por confirmación pendiente
    $this->actingAs($user)
        ->get(route('groups.index', ['period_id' => $period->id, 'confirmed' => 'unconfirmed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupTerms', 1)
            ->where('groupTerms.0.student_count_confirmed', false)
        );
});

test('bulk update persists student count and confirmation across multiple groups in the period', function () {
    $user = User::factory()->coordinator()->create();

    $period = AcademicPeriod::factory()->create(['code' => '202660']);
    $otherPeriod = AcademicPeriod::factory()->create(['code' => '202640']);
    $shift = Shift::factory()->create();

    $groupA = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);
    $groupB = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);

    $termA = GroupTerm::factory()->create([
        'student_group_id' => $groupA->id,
        'academic_period_id' => $period->id,
        'term_number' => 1,
        'student_count' => 18,
        'student_count_confirmed' => false,
    ]);
    $termB = GroupTerm::factory()->create([
        'student_group_id' => $groupB->id,
        'academic_period_id' => $period->id,
        'term_number' => 3,
        'student_count' => 22,
        'student_count_confirmed' => false,
    ]);
    $otherTerm = GroupTerm::factory()->create([
        'student_group_id' => $groupA->id,
        'academic_period_id' => $otherPeriod->id,
        'term_number' => 1,
        'student_count' => 30,
        'student_count_confirmed' => true,
    ]);

    $this->actingAs($user)
        ->from(route('groups.index', ['period_id' => $period->id]))
        ->patch(route('groups.bulk-update'), [
            'academic_period_id' => $period->id,
            'items' => [
                [
                    'id' => $termA->id,
                    'student_count' => 34,
                    'student_count_confirmed' => true,
                ],
                [
                    'id' => $termB->id,
                    'student_count' => 27,
                    'student_count_confirmed' => true,
                ],
            ],
        ])
        ->assertRedirect(route('groups.index', ['period_id' => $period->id]))
        ->assertSessionHas('success');

    expect($termA->fresh())
        ->student_count->toBe(34)
        ->student_count_confirmed->toBeTrue();

    expect($termB->fresh())
        ->student_count->toBe(27)
        ->student_count_confirmed->toBeTrue();

    // Rechaza intentar modificar un GroupTerm de otro periodo
    $this->actingAs($user)
        ->patch(route('groups.bulk-update'), [
            'academic_period_id' => $period->id,
            'items' => [
                [
                    'id' => $otherTerm->id,
                    'student_count' => 5,
                    'student_count_confirmed' => false,
                ],
            ],
        ])
        ->assertSessionHasErrors('items.0.id');

    expect($otherTerm->fresh()->student_count)->toBe(30);
});

test('storing a student group validates curriculum and shift modality and generates sections', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $regular = Modality::factory()->regular()->create();
    $executive = Modality::factory()->executive()->create();

    $programRegular = Program::factory()->create(['code' => 'ADME', 'modality_id' => $regular->id]);
    $programExecutive = Program::factory()->create(['code' => 'EJAD', 'modality_id' => $executive->id]);

    $curriculumRegular = Curriculum::factory()->create(['program_id' => $programRegular->id]);
    $curriculumExecutive = Curriculum::factory()->create(['program_id' => $programExecutive->id]);

    $shiftRegular = Shift::factory()->create(['modality_id' => $regular->id]);
    $shiftExecutive = Shift::factory()->create(['modality_id' => $executive->id]);

    $course1 = Course::factory()->create(['course_code' => 'ADM101', 'name' => 'Introducción a la Administración']);
    $course2 = Course::factory()->create(['course_code' => 'CON101', 'name' => 'Contabilidad I']);

    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculumRegular->id,
        'course_id' => $course1->id,
        'term_number' => 1,
    ]);
    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculumRegular->id,
        'course_id' => $course2->id,
        'term_number' => 1,
    ]);

    $period = AcademicPeriod::factory()->create(['code' => '202660']);

    // Plan de estudios de otra carrera
    $this->actingAs($user)
        ->post(route('groups.store'), [
            'academic_period_id' => $period->id,
            'program_id' => $programRegular->id,
            'curriculum_id' => $curriculumExecutive->id,
            'shift_id' => $shiftRegular->id,
            'term_number' => 1,
            'label' => 'ADME-2026-ERR1',
            'student_count' => 25,
            'student_count_confirmed' => true,
            'generate_sections' => true,
            'suggest_teachers' => true,
        ])
        ->assertSessionHasErrors('curriculum_id');

    // Turno de otra modalidad
    $this->actingAs($user)
        ->post(route('groups.store'), [
            'academic_period_id' => $period->id,
            'program_id' => $programRegular->id,
            'curriculum_id' => $curriculumRegular->id,
            'shift_id' => $shiftExecutive->id,
            'term_number' => 1,
            'label' => 'ADME-2026-ERR2',
            'student_count' => 25,
            'student_count_confirmed' => true,
            'generate_sections' => true,
            'suggest_teachers' => true,
        ])
        ->assertSessionHasErrors('shift_id');

    // Creación válida con generación de secciones
    $this->actingAs($user)
        ->post(route('groups.store'), [
            'academic_period_id' => $period->id,
            'program_id' => $programRegular->id,
            'curriculum_id' => $curriculumRegular->id,
            'shift_id' => $shiftRegular->id,
            'term_number' => 1,
            'label' => 'ADME-2026-M1',
            'student_count' => 31,
            'student_count_confirmed' => true,
            'generate_sections' => true,
            'suggest_teachers' => true,
        ])
        ->assertRedirect(route('groups.index', ['period_id' => $period->id]))
        ->assertSessionHas('success');

    $group = StudentGroup::query()->where('label', 'ADME-2026-M1')->first();
    expect($group)->not->toBeNull();

    $groupTerm = GroupTerm::query()
        ->where('student_group_id', $group->id)
        ->where('academic_period_id', $period->id)
        ->first();

    expect($groupTerm)
        ->not->toBeNull()
        ->term_number->toBe(1)
        ->student_count->toBe(31)
        ->student_count_confirmed->toBeTrue();

    expect($groupTerm->sections()->count())->toBe(2);
});
