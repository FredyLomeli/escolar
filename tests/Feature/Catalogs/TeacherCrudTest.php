<?php

use App\Enums\AvailabilityType;
use App\Enums\TeacherCourseSource;
use App\Enums\TeacherOrigin;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherCourse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access teachers catalog', function () {
    $teacher = Teacher::factory()->create();

    $this->get(route('catalogs.teachers.index'))->assertRedirect(route('login'));
    $this->get(route('catalogs.teachers.show', $teacher))->assertRedirect(route('login'));
});

test('authenticated users can list and filter teachers without lazy loading errors', function () {
    $user = User::factory()->coordinator()->create();
    Teacher::factory()->create(['employee_id' => '00100001', 'full_name' => 'Sofía Mendoza', 'origin' => TeacherOrigin::Campus]);
    Teacher::factory()->create(['employee_id' => '00100002', 'full_name' => 'Héctor Campos', 'origin' => TeacherOrigin::External]);

    $this->actingAs($user)
        ->get(route('catalogs.teachers.index', ['origin' => TeacherOrigin::Campus->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Teachers/Index')
            ->has('teachers.data', 1)
            ->where('teachers.data.0.employee_id', '00100001')
        );
});

test('teachers normalize numeric employee_id to 8 digits and support update and toggle active', function () {
    $user = User::factory()->coordinator()->create();

    $this->actingAs($user)
        ->post(route('catalogs.teachers.store'), [
            'employee_id' => '247112',
            'full_name' => 'Mtra. Laura Domínguez',
            'email' => 'LAURA@EJEMPLO.TEST',
            'origin' => TeacherOrigin::Campus->value,
            'max_weekly_hours' => 24,
        ])
        ->assertSessionHasNoErrors();

    $teacher = Teacher::query()->where('employee_id', '00247112')->firstOrFail();
    expect($teacher->email)->toBe('laura@ejemplo.test');

    // ID duplicado rechazado (incluso sin ceros a la izquierda)
    $this->actingAs($user)
        ->post(route('catalogs.teachers.store'), [
            'employee_id' => '247112',
            'full_name' => 'Duplicado',
            'origin' => TeacherOrigin::Campus->value,
        ])
        ->assertSessionHasErrors('employee_id');

    // Edición
    $this->actingAs($user)
        ->put(route('catalogs.teachers.update', $teacher), [
            'employee_id' => '00247112',
            'full_name' => 'Dra. Laura Domínguez',
            'email' => '',
            'origin' => TeacherOrigin::External->value,
            'max_weekly_hours' => 18,
        ])
        ->assertSessionHasNoErrors();

    $teacher->refresh();
    expect($teacher->full_name)->toBe('Dra. Laura Domínguez')
        ->and($teacher->origin)->toBe(TeacherOrigin::External)
        ->and($teacher->max_weekly_hours)->toBe(18)
        ->and($teacher->email)->toBeNull();

    // Desactivar y reactivar
    $this->actingAs($user)
        ->patch(route('catalogs.teachers.toggle-active', $teacher))
        ->assertRedirect();
    expect($teacher->fresh()?->active)->toBeFalse();
});

test('teacher detail supports course profile and weekly availability management', function () {
    $user = User::factory()->coordinator()->create();
    $teacher = Teacher::factory()->create();
    $course = Course::factory()->create(['subject_code' => 'ADME', 'course_code' => 'ADM11', 'name' => 'Administración']);

    $this->actingAs($user)
        ->get(route('catalogs.teachers.show', $teacher))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Teachers/Show')
            ->has('availableCourses', 1)
            ->has('availabilityTypeOptions', 2)
        );

    // Agregar materia al perfil
    $this->actingAs($user)
        ->post(route('catalogs.teachers.courses.store', $teacher), [
            'course_id' => $course->id,
        ])
        ->assertSessionHasNoErrors();

    $teacherCourse = TeacherCourse::query()->where('teacher_id', $teacher->id)->firstOrFail();
    expect($teacherCourse->source)->toBe(TeacherCourseSource::Manual);

    // Materia duplicada rechazada
    $this->actingAs($user)
        ->post(route('catalogs.teachers.courses.store', $teacher), [
            'course_id' => $course->id,
        ])
        ->assertSessionHasErrors('course_id');

    // Quitar del perfil sin borrar materia global
    $this->actingAs($user)
        ->delete(route('catalogs.teacher-courses.destroy', $teacherCourse))
        ->assertRedirect();

    expect($teacher->teacherCourses()->count())->toBe(0)
        ->and(Course::query()->whereKey($course->id)->exists())->toBeTrue();

    // Validación de horas en disponibilidad
    $this->actingAs($user)
        ->post(route('catalogs.teachers.availabilities.store', $teacher), [
            'days_of_week' => [1],
            'starts_at' => '15:00',
            'ends_at' => '13:00',
            'type' => AvailabilityType::Available->value,
        ])
        ->assertSessionHasErrors('ends_at');

    // Alta multi-día de disponibilidad
    $this->actingAs($user)
        ->post(route('catalogs.teachers.availabilities.store', $teacher), [
            'days_of_week' => [1, 3, 5],
            'starts_at' => '08:00',
            'ends_at' => '14:00',
            'type' => AvailabilityType::Available->value,
        ])
        ->assertSessionHasNoErrors();

    expect($teacher->availabilities()->count())->toBe(3);

    $slot = TeacherAvailability::query()->where('teacher_id', $teacher->id)->firstOrFail();
    $this->actingAs($user)
        ->delete(route('catalogs.teacher-availabilities.destroy', $slot))
        ->assertRedirect();

    expect($teacher->availabilities()->count())->toBe(2);
});
