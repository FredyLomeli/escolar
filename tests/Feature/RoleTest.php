<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Gate;

test('new users default to coordinator role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Coordinator)
        ->and($user->isCoordinator())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->role->label())->toBe('Coordinador');
});

test('admin user helpers and labels work correctly', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isCoordinator())->toBeFalse()
        ->and($admin->role->label())->toBe('Administrador');
});

test('authorization gates enforce role boundaries', function () {
    $admin = User::factory()->admin()->create();
    $coordinator = User::factory()->coordinator()->create();

    expect(Gate::forUser($admin)->allows('manage-users'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage-catalogs'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage-planning'))->toBeTrue()
        ->and(Gate::forUser($coordinator)->denies('manage-users'))->toBeTrue()
        ->and(Gate::forUser($coordinator)->allows('manage-catalogs'))->toBeTrue()
        ->and(Gate::forUser($coordinator)->allows('manage-planning'))->toBeTrue();
});

test('user policy allows admin to manage other users and blocks coordinator', function () {
    $admin = User::factory()->admin()->create();
    $coordinator = User::factory()->coordinator()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('create', User::class))->toBeTrue()
        ->and($admin->can('update', $coordinator))->toBeTrue()
        ->and($admin->can('delete', $coordinator))->toBeTrue()
        ->and($admin->can('delete', $admin))->toBeFalse()
        ->and($coordinator->can('viewAny', User::class))->toBeFalse()
        ->and($coordinator->can('create', User::class))->toBeFalse()
        ->and($coordinator->can('update', $admin))->toBeFalse()
        ->and($coordinator->can('delete', $admin))->toBeFalse();
});

test('database seeder creates initial admin and coordinator accounts idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@escolar.test')->first();
    $coordinator = User::where('email', 'coordinador@escolar.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($coordinator)->not->toBeNull()
        ->and($coordinator->isCoordinator())->toBeTrue()
        ->and(User::count())->toBe(2);
});
