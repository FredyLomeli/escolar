<?php

use App\Models\Modality;
use App\Models\ReservedBlock;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access shifts catalog', function () {
    $this->get(route('catalogs.shifts.index'))->assertRedirect(route('login'));
});

test('authenticated users can list and filter shifts with windows and reserved blocks', function () {
    $user = User::factory()->coordinator()->create();
    $regular = Modality::factory()->regular()->create();
    $executive = Modality::factory()->executive()->create();

    $morning = Shift::factory()->create(['modality_id' => $regular->id, 'code' => 'matutino', 'name' => 'Matutino']);
    ShiftWindow::factory()->create(['shift_id' => $morning->id, 'day_of_week' => 1]);
    ReservedBlock::factory()->create(['shift_id' => $morning->id, 'name' => 'Inglés', 'day_of_week' => 1]);

    Shift::factory()->create(['modality_id' => $executive->id, 'code' => 'sabado', 'name' => 'Sábado']);

    $this->actingAs($user)
        ->get(route('catalogs.shifts.index', ['modality_id' => $regular->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Shifts/Index')
            ->has('shifts', 1)
            ->where('shifts.0.code', 'matutino')
            ->has('shifts.0.windows', 1)
            ->has('shifts.0.reserved_blocks', 1)
        );
});

test('shifts can be created, updated, and deactivated with unique code', function () {
    $user = User::factory()->coordinator()->create();
    $modality = Modality::factory()->executive()->create();

    $this->actingAs($user)
        ->post(route('catalogs.shifts.store'), [
            'modality_id' => $modality->id,
            'code' => 'Ejecutivo Domingo',
            'name' => 'Ejecutivo Domingo',
        ])
        ->assertSessionHasNoErrors();

    $shift = Shift::query()->where('code', 'ejecutivo-domingo')->firstOrFail();

    // Código duplicado
    $this->actingAs($user)
        ->post(route('catalogs.shifts.store'), [
            'modality_id' => $modality->id,
            'code' => 'ejecutivo-domingo',
            'name' => 'Duplicado',
        ])
        ->assertSessionHasErrors('code');

    // Edición permitiendo su propio código
    $this->actingAs($user)
        ->put(route('catalogs.shifts.update', $shift), [
            'modality_id' => $modality->id,
            'code' => 'ejecutivo-domingo',
            'name' => 'Ejecutivo Dominical',
        ])
        ->assertSessionHasNoErrors();

    expect($shift->fresh()?->name)->toBe('Ejecutivo Dominical');

    // Desactivar y reactivar
    $this->actingAs($user)
        ->patch(route('catalogs.shifts.toggle-active', $shift))
        ->assertRedirect();
    expect($shift->fresh()?->active)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('catalogs.shifts.toggle-active', $shift))
        ->assertRedirect();
    expect($shift->fresh()?->active)->toBeTrue();
});

test('shift windows and reserved blocks support multi-day creation, time validation, and deletion', function () {
    $user = User::factory()->coordinator()->create();
    $shift = Shift::factory()->create();

    // Hora fin menor o igual que hora inicio rechazada
    $this->actingAs($user)
        ->post(route('catalogs.shifts.windows.store', $shift), [
            'days_of_week' => [1],
            'starts_at' => '14:00',
            'ends_at' => '12:00',
        ])
        ->assertSessionHasErrors('ends_at');

    // Alta multi-día idempotente
    $this->actingAs($user)
        ->post(route('catalogs.shifts.windows.store', $shift), [
            'days_of_week' => [1, 2, 3],
            'starts_at' => '08:00',
            'ends_at' => '14:00',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('catalogs.shifts.windows.store', $shift), [
            'days_of_week' => [1, 2],
            'starts_at' => '08:00',
            'ends_at' => '14:00',
        ])
        ->assertSessionHasNoErrors();

    expect($shift->windows()->count())->toBe(3);

    $window = $shift->windows()->firstOrFail();
    $this->actingAs($user)
        ->delete(route('catalogs.shifts.windows.destroy', $window))
        ->assertRedirect();

    expect($shift->windows()->count())->toBe(2);

    // Bloques reservados
    $this->actingAs($user)
        ->post(route('catalogs.shifts.reserved-blocks.store', $shift), [
            'name' => 'Inglés',
            'days_of_week' => [1, 2, 3, 4],
            'starts_at' => '12:30',
            'ends_at' => '14:00',
        ])
        ->assertSessionHasNoErrors();

    expect($shift->reservedBlocks()->count())->toBe(4);

    $block = $shift->reservedBlocks()->firstOrFail();
    $this->actingAs($user)
        ->delete(route('catalogs.shifts.reserved-blocks.destroy', $block))
        ->assertRedirect();

    expect($shift->reservedBlocks()->count())->toBe(3);
});
