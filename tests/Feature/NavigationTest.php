<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login for navigation routes', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'dashboard',
    'planning.index',
    'groups.index',
    'catalogs.programs.index',
    'catalogs.courses.index',
    'catalogs.teachers.index',
    'catalogs.classrooms.index',
    'catalogs.shifts.index',
    'catalogs.settings.index',
    'import.index',
    'users.index',
]);

test('authenticated users can visit all standard navigation sections', function (string $route) {
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route($route))
        ->assertOk();
})->with([
    'dashboard',
    'planning.index',
    'groups.index',
    'catalogs.programs.index',
    'catalogs.courses.index',
    'catalogs.teachers.index',
    'catalogs.classrooms.index',
    'catalogs.shifts.index',
    'catalogs.settings.index',
    'import.index',
]);

test('users section is accessible only to admins', function () {
    $admin = User::factory()->admin()->create();
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SectionPlaceholder')
            ->where('auth.user.role', 'admin')
            ->where('auth.user.role_label', 'Administrador')
            ->where('auth.user.is_admin', true)
        );

    $this->actingAs($coordinator)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('dashboard shares coordinator role props with inertia', function () {
    $coordinator = User::factory()->coordinator()->create();

    $this->actingAs($coordinator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.role', 'coordinator')
            ->where('auth.user.role_label', 'Coordinador')
            ->where('auth.user.is_admin', false)
        );
});
