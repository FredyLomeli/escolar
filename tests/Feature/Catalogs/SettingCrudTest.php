<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can view and update system settings', function () {
    $this->seed(SettingsSeeder::class);
    $user = User::factory()->coordinator()->create();

    $this->actingAs($user)
        ->get(route('catalogs.settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogs/Settings/Index')
            ->has('settings', 9)
            ->has('deliveryOptions', 4)
        );

    $targetSize = Setting::query()->where('key', 'target_section_size')->firstOrFail();

    $this->actingAs($user)
        ->put(route('catalogs.settings.update', $targetSize), [
            'value' => 22,
            'description' => 'Tamaño actualizado',
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('target_section_size'))->toBe(22);

    // Rechaza valor inválido (0)
    $this->actingAs($user)
        ->put(route('catalogs.settings.update', $targetSize), [
            'value' => 0,
        ])
        ->assertSessionHasErrors('value');

    // Parámetro booleano
    $allowWarnings = Setting::query()->where('key', 'allow_publish_with_warnings')->firstOrFail();
    $this->actingAs($user)
        ->put(route('catalogs.settings.update', $allowWarnings), [
            'value' => false,
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('allow_publish_with_warnings'))->toBeFalse();

    // Marcadores Excel
    $markers = Setting::query()->where('key', 'excel_teacher_markers')->firstOrFail();
    $this->actingAs($user)
        ->put(route('catalogs.settings.update', $markers), [
            'value' => [
                '@' => 'online_async',
                'VV' => 'online_live',
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::getValue('excel_teacher_markers'))->toBe([
        '@' => 'online_async',
        'VV' => 'online_live',
    ]);

    // Rechaza tipo de entrega inválido en marcadores
    $this->actingAs($user)
        ->put(route('catalogs.settings.update', $markers), [
            'value' => [
                '@' => 'invalido',
            ],
        ])
        ->assertSessionHasErrors('value.*');
});
