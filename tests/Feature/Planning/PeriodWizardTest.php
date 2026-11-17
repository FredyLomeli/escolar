<?php

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\GroupTerm;
use App\Models\Modality;
use App\Models\Program;
use App\Models\Section;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access period wizard or endpoints', function () {
    $period = AcademicPeriod::factory()->create();

    $this->get(route('periods.wizard'))->assertRedirect(route('login'));
    $this->get(route('periods.preview', $period))->assertRedirect(route('login'));
    $this->post(route('periods.store'), [])->assertRedirect(route('login'));
});

test('dashboard displays real period metrics and recommendations', function () {
    $user = User::factory()->coordinator()->create();

    // Sin periodos
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('selectedPeriod', null)
            ->where('stats', null)
        );

    // Con periodo y secciones pendientes
    $period = AcademicPeriod::factory()->create(['code' => '202630', 'status' => AcademicPeriodStatus::Draft]);
    $shift = Shift::factory()->create();
    $group = StudentGroup::factory()->create(['shift_id' => $shift->id, 'entry_period_id' => $period->id]);
    GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $period->id,
        'student_count_confirmed' => false,
    ]);
    Section::factory()->create([
        'academic_period_id' => $period->id,
        'shift_id' => $shift->id,
        'delivery' => DeliveryType::InPerson,
        'teacher_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['period_id' => $period->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('selectedPeriod.code', '202630')
            ->where('stats.total_sections', 1)
            ->where('stats.sections_without_teacher', 1)
            ->where('stats.sections_without_schedule', 1)
            ->where('stats.unconfirmed_groups', 1)
            ->has('recommendations', 3)
        );
});

test('wizard suggests next period, returns json preview, and advances period via http', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ADME', 'modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);
    $course = Course::factory()->create();
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $course->id, 'term_number' => 2]);

    $source = AcademicPeriod::factory()->create([
        'code' => '202660',
        'name' => 'Ago – Dic 2026',
        'year' => 2026,
        'term_of_year' => 3,
        'weeks' => 15,
        'status' => AcademicPeriodStatus::Published,
    ]);

    $g1 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $source->id,
        'label' => 'ADME-2026-M',
    ]);
    $g9 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $source->id,
        'label' => 'ADME-2023-M',
    ]);

    GroupTerm::factory()->create(['student_group_id' => $g1->id, 'academic_period_id' => $source->id, 'term_number' => 1, 'student_count' => 20]);
    GroupTerm::factory()->create(['student_group_id' => $g9->id, 'academic_period_id' => $source->id, 'term_number' => 9, 'student_count' => 15]);

    $this->actingAs($user)
        ->get(route('periods.wizard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Periods/Wizard')
            ->where('suggested.code', '202720')
            ->where('suggested.year', 2027)
            ->where('suggested.term_of_year', 1)
            ->where('suggested.weeks', 15)
            ->has('initialPreview.advancing', 1)
            ->has('initialPreview.graduating', 1)
        );

    $this->actingAs($user)
        ->getJson(route('periods.preview', $source))
        ->assertOk()
        ->assertJsonCount(1, 'advancing')
        ->assertJsonCount(1, 'graduating');

    // Validación de código duplicado y fechas inválidas
    $this->actingAs($user)
        ->post(route('periods.store'), [
            'code' => '202660',
            'name' => 'Duplicado',
            'year' => 2027,
            'term_of_year' => 1,
            'starts_on' => '2027-04-01',
            'ends_on' => '2027-01-01',
            'weeks' => 15,
            'copy_meetings' => false,
            'suggest_teachers' => true,
        ])
        ->assertSessionHasErrors(['code', 'ends_on']);

    // Creación completa avanzando desde $source
    $this->actingAs($user)
        ->post(route('periods.store'), [
            'source_period_id' => $source->id,
            'code' => '202720',
            'name' => 'Ene – May 2027',
            'year' => 2027,
            'term_of_year' => 1,
            'starts_on' => '2027-01-11',
            'ends_on' => '2027-05-01',
            'weeks' => 15,
            'copy_meetings' => false,
            'suggest_teachers' => true,
            'student_count_overrides' => [
                $g1->id => 26,
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $newPeriod = AcademicPeriod::query()->where('code', '202720')->firstOrFail();

    expect($g9->fresh()?->status)->toBe(StudentGroupStatus::Graduated)
        ->and(GroupTerm::where('academic_period_id', $newPeriod->id)->value('student_count'))->toBe(26)
        ->and(Section::where('academic_period_id', $newPeriod->id)->count())->toBe(1);
});

test('wizard can create an empty period without source period', function () {
    $user = User::factory()->coordinator()->create();

    $this->actingAs($user)
        ->post(route('periods.store'), [
            'source_period_id' => null,
            'code' => '202720',
            'name' => 'May – Ago 2027',
            'year' => 2027,
            'term_of_year' => 2,
            'starts_on' => '2027-05-11',
            'ends_on' => '2027-08-21',
            'weeks' => 14,
            'copy_meetings' => false,
            'suggest_teachers' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $period = AcademicPeriod::query()->where('code', '202720')->firstOrFail();
    expect($period->status)->toBe(AcademicPeriodStatus::Draft)
        ->and($period->sections()->count())->toBe(0);
});
