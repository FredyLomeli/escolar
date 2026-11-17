<?php

use App\Models\Course;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can list and filter courses', function () {
    $user = User::factory()->coordinator()->create();
    Course::factory()->create(['subject_code' => 'LMFM', 'course_code' => 'HUS02', 'name' => 'Razonamiento Crítico']);
    Course::factory()->create(['subject_code' => 'ADME', 'course_code' => 'ADM11', 'name' => 'Administración', 'active' => false]);

    $this->actingAs($user)
        ->get(route('catalogs.courses.index', ['search' => 'HUS02']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.course_code', 'HUS02')
        );
});

test('courses enforce unique SUBJ + CRSE pair and allow check-pair lookup', function () {
    $user = User::factory()->coordinator()->create();

    $this->actingAs($user)
        ->post(route('catalogs.courses.store'), [
            'subject_code' => ' lmfm ',
            'course_code' => ' hus02 ',
            'name' => 'Razonamiento Crítico',
        ])
        ->assertSessionHasNoErrors();

    $course = Course::query()->where('subject_code', 'LMFM')->where('course_code', 'HUS02')->firstOrFail();

    // Misma pareja rechazada
    $this->actingAs($user)
        ->post(route('catalogs.courses.store'), [
            'subject_code' => 'LMFM',
            'course_code' => 'HUS02',
            'name' => 'Otra Materia',
        ])
        ->assertSessionHasErrors('course_code');

    // Mismo SUBJ con diferente CRSE permitido
    $this->actingAs($user)
        ->post(route('catalogs.courses.store'), [
            'subject_code' => 'LMFM',
            'course_code' => 'HUS03',
            'name' => 'Ética Profesional',
        ])
        ->assertSessionHasNoErrors();

    // Endpoint de aviso en vivo
    $this->actingAs($user)
        ->getJson(route('catalogs.courses.check-pair', ['subject_code' => 'lmfm', 'course_code' => 'hus02']))
        ->assertOk()
        ->assertJson(['exists' => true]);

    $this->actingAs($user)
        ->getJson(route('catalogs.courses.check-pair', [
            'subject_code' => 'LMFM',
            'course_code' => 'HUS02',
            'ignore_id' => $course->id,
        ]))
        ->assertOk()
        ->assertJson(['exists' => false]);

    // Edición y desactivación
    $this->actingAs($user)
        ->put(route('catalogs.courses.update', $course), [
            'subject_code' => 'LMFM',
            'course_code' => 'HUS02',
            'name' => 'Razonamiento Crítico II',
        ])
        ->assertSessionHasNoErrors();

    expect($course->fresh()?->name)->toBe('Razonamiento Crítico II');

    $this->actingAs($user)
        ->patch(route('catalogs.courses.toggle-active', $course))
        ->assertRedirect();

    expect($course->fresh()?->active)->toBeFalse();
});
