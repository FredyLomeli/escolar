<?php

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Modality;
use App\Models\Program;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access programs or curricula catalogs', function () {
    $curriculum = Curriculum::factory()->create();

    $this->get(route('catalogs.programs.index'))->assertRedirect(route('login'));
    $this->get(route('catalogs.curricula.show', $curriculum))->assertRedirect(route('login'));
});

test('programs can be listed, filtered, created, updated, and deactivated', function () {
    $user = User::factory()->coordinator()->create();
    $regular = Modality::factory()->regular()->create();
    $executive = Modality::factory()->executive()->create();

    Program::factory()->create(['code' => 'ADME', 'name' => 'Administración', 'modality_id' => $regular->id]);
    Program::factory()->create(['code' => 'EJAD', 'name' => 'Administración Ejecutiva', 'modality_id' => $executive->id]);

    $this->actingAs($user)
        ->get(route('catalogs.programs.index', ['modality_id' => $regular->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Programs/Index')
            ->has('programs.data', 1)
            ->where('programs.data.0.code', 'ADME')
        );

    $this->actingAs($user)
        ->post(route('catalogs.programs.store'), [
            'code' => ' disgd ',
            'name' => 'Diseño Gráfico Digital',
            'modality_id' => $regular->id,
        ])
        ->assertSessionHasNoErrors();

    $program = Program::query()->where('code', 'DISGD')->firstOrFail();

    // Código duplicado rechazado
    $this->actingAs($user)
        ->post(route('catalogs.programs.store'), [
            'code' => 'disgd',
            'name' => 'Duplicada',
            'modality_id' => $regular->id,
        ])
        ->assertSessionHasErrors('code');

    // Edición y desactivación
    $this->actingAs($user)
        ->put(route('catalogs.programs.update', $program), [
            'code' => 'DISGD',
            'name' => 'Licenciatura en Diseño Gráfico',
            'modality_id' => $regular->id,
        ])
        ->assertSessionHasNoErrors();

    expect($program->fresh()?->name)->toBe('Licenciatura en Diseño Gráfico');

    $this->actingAs($user)
        ->patch(route('catalogs.programs.toggle-active', $program))
        ->assertRedirect();

    expect($program->fresh()?->active)->toBeFalse();
});

test('curricula can be created with default rules or cloned from previous version', function () {
    $user = User::factory()->coordinator()->create();
    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ARQ', 'modality_id' => $regular->id]);

    // Crear primer plan sin clonar
    $this->actingAs($user)
        ->post(route('catalogs.programs.curricula.store', $program), [
            'version' => '2026',
            'valid_from' => '2026-01-01',
        ])
        ->assertSessionHasNoErrors();

    $plan2026 = Curriculum::query()->where('program_id', $program->id)->where('version', '2026')->firstOrFail();
    expect($plan2026->termRules()->count())->toBe(9)
        ->and($plan2026->termRules()->where('term_number', 1)->value('expected_courses'))->toBe(7)
        ->and($plan2026->termRules()->where('term_number', 7)->value('expected_courses'))->toBe(1);

    // Agregar una materia al plan 2026
    $course = Course::factory()->create(['subject_code' => 'ARQ', 'course_code' => 'DIS01', 'name' => 'Dibujo Arquitectónico']);
    CurriculumCourse::factory()->create([
        'curriculum_id' => $plan2026->id,
        'course_id' => $course->id,
        'term_number' => 1,
    ]);

    // Crear plan 2027 clonando desde 2026
    $this->actingAs($user)
        ->post(route('catalogs.programs.curricula.store', $program), [
            'version' => '2027',
            'clone_from_curriculum_id' => $plan2026->id,
        ])
        ->assertSessionHasNoErrors();

    $plan2027 = Curriculum::query()->where('program_id', $program->id)->where('version', '2027')->firstOrFail();
    expect($plan2027->termRules()->count())->toBe(9)
        ->and($plan2027->curriculumCourses()->count())->toBe(1);

    // Versión duplicada en la misma carrera rechazada
    $this->actingAs($user)
        ->post(route('catalogs.programs.curricula.store', $program), [
            'version' => '2027',
        ])
        ->assertSessionHasErrors('version');

    // Fecha fin menor a inicio rechazada
    $this->actingAs($user)
        ->put(route('catalogs.curricula.update', $plan2026), [
            'version' => '2026',
            'valid_from' => '2026-06-01',
            'valid_to' => '2026-01-01',
        ])
        ->assertSessionHasErrors('valid_to');
});

test('curriculum grid supports term rules, adding, updating, and removing courses without deleting catalog course', function () {
    $user = User::factory()->coordinator()->create();
    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'DERE', 'modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id, 'version' => '2026']);
    $course = Course::factory()->create(['subject_code' => 'DERE', 'course_code' => 'CIV01', 'name' => 'Derecho Civil I']);

    $this->actingAs($user)
        ->get(route('catalogs.curricula.show', $curriculum))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Curricula/Show')
            ->has('availableCourses', 1)
            ->has('kindOptions', 3)
            ->has('deliveryOptions', 4)
        );

    // Actualizar regla de materias esperadas del cuatrimestre 7
    $this->actingAs($user)
        ->put(route('catalogs.curricula.term-rules.update', [
            'curriculum' => $curriculum,
            'termNumber' => 7,
        ]), [
            'expected_courses' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($curriculum->termRules()->where('term_number', 7)->value('expected_courses'))->toBe(2);

    // Agregar materia al plan
    $this->actingAs($user)
        ->post(route('catalogs.curricula.courses.store', $curriculum), [
            'course_id' => $course->id,
            'term_number' => 1,
            'kind' => CourseKind::Regular->value,
            'default_delivery' => DeliveryType::InPerson->value,
            'weekly_hours' => 4.0,
        ])
        ->assertSessionHasNoErrors();

    $item = $curriculum->curriculumCourses()->firstOrFail();

    // Materia duplicada en el mismo plan rechazada
    $this->actingAs($user)
        ->post(route('catalogs.curricula.courses.store', $curriculum), [
            'course_id' => $course->id,
            'term_number' => 2,
            'kind' => CourseKind::Regular->value,
            'default_delivery' => DeliveryType::InPerson->value,
        ])
        ->assertSessionHasErrors('course_id');

    // Editar asignación (mover a cuatrimestre 2 y cambiar entrega)
    $this->actingAs($user)
        ->put(route('catalogs.curriculum-courses.update', $item), [
            'term_number' => 2,
            'kind' => CourseKind::Additional->value,
            'default_delivery' => DeliveryType::OnlineConnect->value,
            'weekly_hours' => 2.0,
            'display_name' => 'Introducción Civil',
        ])
        ->assertSessionHasNoErrors();

    $item->refresh();
    expect($item->term_number)->toBe(2)
        ->and($item->kind)->toBe(CourseKind::Additional)
        ->and($item->default_delivery)->toBe(DeliveryType::OnlineConnect);

    // Verificar que Courses/Index muestra los planes vinculados sin N+1
    $this->actingAs($user)
        ->get(route('catalogs.courses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Courses/Index')
            ->has('courses.data.0.curriculum_courses', 1)
        );

    // Retirar materia del plan sin borrarla del catálogo
    $this->actingAs($user)
        ->delete(route('catalogs.curriculum-courses.destroy', $item))
        ->assertRedirect();

    expect($curriculum->curriculumCourses()->count())->toBe(0)
        ->and(Course::query()->whereKey($course->id)->exists())->toBeTrue();
});
