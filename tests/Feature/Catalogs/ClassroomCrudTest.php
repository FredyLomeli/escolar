<?php

use App\Models\Classroom;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access classrooms catalog', function () {
    $this->get(route('catalogs.classrooms.index'))->assertRedirect(route('login'));
});

test('authenticated users can list and filter classrooms', function () {
    $user = User::factory()->coordinator()->create();
    Classroom::factory()->create(['code' => 'CTC 01', 'name' => 'Centro de Cómputo', 'active' => true]);
    Classroom::factory()->create(['code' => '999', 'name' => 'Inactiva', 'active' => false]);

    $this->actingAs($user)
        ->get(route('catalogs.classrooms.index', ['search' => 'CTC', 'status' => 'active']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Classrooms/Index')
            ->has('classrooms.data', 1)
            ->where('classrooms.data.0.code', 'CTC 01')
        );
});

test('classrooms can be created, updated, and deactivated with unique code enforcement', function () {
    $user = User::factory()->coordinator()->create();

    $this->actingAs($user)
        ->post(route('catalogs.classrooms.store'), [
            'code' => ' lab 01 ',
            'name' => 'Laboratorio 01',
            'capacity' => 28,
        ])
        ->assertRedirect();

    $classroom = Classroom::query()->where('code', 'LAB 01')->firstOrFail();

    expect($classroom->name)->toBe('Laboratorio 01')
        ->and($classroom->capacity)->toBe(28)
        ->and($classroom->active)->toBeTrue();

    // Código duplicado rechazado
    $this->actingAs($user)
        ->post(route('catalogs.classrooms.store'), [
            'code' => 'lab 01',
            'name' => 'Duplicada',
            'capacity' => 20,
        ])
        ->assertSessionHasErrors('code');

    // Edición permitiendo el mismo código
    $this->actingAs($user)
        ->put(route('catalogs.classrooms.update', $classroom), [
            'code' => 'LAB 01',
            'name' => 'Laboratorio Renovado',
            'capacity' => 32,
        ])
        ->assertSessionHasNoErrors();

    expect($classroom->fresh()?->capacity)->toBe(32);

    // Desactivar y reactivar
    $this->actingAs($user)
        ->patch(route('catalogs.classrooms.toggle-active', $classroom))
        ->assertRedirect();
    expect($classroom->fresh()?->active)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('catalogs.classrooms.toggle-active', $classroom))
        ->assertRedirect();
    expect($classroom->fresh()?->active)->toBeTrue();
});
