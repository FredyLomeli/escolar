<?php

namespace Database\Seeders;

use App\Enums\DeliveryType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed default configurable parameters without overwriting existing user changes.
     */
    public function run(): void
    {
        $defaults = [
            'target_section_size' => [
                'value' => 20,
                'description' => 'Tamaño objetivo de alumnos por sección (referencia para compactación).',
            ],
            'section_size_soft_min' => [
                'value' => 10,
                'description' => 'Mínimo suave de alumnos por sección antes de mostrar advertencia.',
            ],
            'section_size_soft_max' => [
                'value' => 30,
                'description' => 'Máximo suave de alumnos por sección antes de mostrar advertencia.',
            ],
            'weeks_in_term_regular' => [
                'value' => 14,
                'description' => 'Duración estándar en semanas para periodos de modalidad escolarizada.',
            ],
            'weeks_in_module_executive' => [
                'value' => 5,
                'description' => 'Duración en semanas de cada módulo en modalidad ejecutiva.',
            ],
            'modules_per_term_executive' => [
                'value' => 3,
                'description' => 'Cantidad de módulos por cuatrimestre en modalidad ejecutiva.',
            ],
            'proposal_history_periods' => [
                'value' => 3,
                'description' => 'Número de periodos históricos recientes considerados al proponer docentes.',
            ],
            'excel_teacher_markers' => [
                'value' => [
                    '@' => DeliveryType::OnlineAsync->value,
                    'VV' => DeliveryType::OnlineLive->value,
                    'CC' => DeliveryType::OnlineConnect->value,
                ],
                'description' => 'Mapeo editable de marcadores especiales en la columna DOCENTE del importador Excel.',
            ],
            'allow_publish_with_warnings' => [
                'value' => true,
                'description' => 'Permite publicar un periodo cuando solo existen advertencias (los errores siempre bloquean).',
            ],
        ];

        foreach ($defaults as $key => $payload) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => $payload['value'],
                    'description' => $payload['description'],
                ],
            );
        }
    }
}
