<?php

namespace App\Http\Controllers\Catalogs;

use App\Enums\DeliveryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Setting::class);

        $settings = Setting::query()
            ->orderBy('key')
            ->get()
            ->map(fn (Setting $setting): array => [
                'id' => $setting->id,
                'key' => $setting->key,
                'label' => $this->settingLabel($setting->key),
                'input_type' => $this->settingInputType($setting->key),
                'value' => Setting::getValue($setting->key),
                'description' => $setting->description,
            ]);

        $deliveryOptions = collect(DeliveryType::cases())
            ->map(fn (DeliveryType $delivery): array => [
                'value' => $delivery->value,
                'label' => $delivery->label(),
            ])
            ->values();

        return Inertia::render('Catalogs/Settings/Index', [
            'settings' => $settings,
            'deliveryOptions' => $deliveryOptions,
        ]);
    }

    public function update(UpdateSettingRequest $request, Setting $setting): RedirectResponse
    {
        $validated = $request->validated();

        $payload = ['value' => $validated['value']];

        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }

        $setting->update($payload);

        return back()->with('success', 'Parámetro actualizado correctamente.');
    }

    private function settingLabel(string $key): string
    {
        return match ($key) {
            'target_section_size' => 'Tamaño objetivo de sección',
            'section_size_soft_min' => 'Mínimo suave de alumnos por sección',
            'section_size_soft_max' => 'Máximo suave de alumnos por sección',
            'weeks_in_term_regular' => 'Semanas en periodo escolarizado',
            'weeks_in_module_executive' => 'Semanas por módulo ejecutivo',
            'modules_per_term_executive' => 'Módulos por periodo ejecutivo',
            'proposal_history_periods' => 'Periodos históricos para proponer docente',
            'excel_teacher_markers' => 'Marcadores especiales en columna DOCENTE (Excel)',
            'allow_publish_with_warnings' => 'Permitir publicar con advertencias',
            default => $key,
        };
    }

    private function settingInputType(string $key): string
    {
        return match ($key) {
            'allow_publish_with_warnings' => 'boolean',
            'excel_teacher_markers' => 'markers',
            default => 'number',
        };
    }
}
