<?php

namespace App\Http\Requests\Catalogs;

use App\Enums\DeliveryType;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $setting = $this->route('setting');

        return $setting instanceof Setting
            && ($this->user()?->can('update', $setting) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $setting = $this->route('setting');

        if ($setting instanceof Setting && $setting->key === 'excel_teacher_markers' && is_array($this->input('value'))) {
            $cleaned = [];
            foreach ($this->input('value') as $marker => $delivery) {
                $trimmedKey = trim((string) $marker);
                if ($trimmedKey !== '') {
                    $cleaned[$trimmedKey] = $delivery;
                }
            }
            $this->merge(['value' => $cleaned]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Setting $setting */
        $setting = $this->route('setting');

        $valueRules = match ($setting->key) {
            'allow_publish_with_warnings' => ['required', 'boolean'],
            'excel_teacher_markers' => ['required', 'array', 'min:1'],
            default => ['required', 'integer', 'min:1', 'max:200'],
        };

        $rules = [
            'value' => $valueRules,
            'description' => ['nullable', 'string', 'max:255'],
        ];

        if ($setting->key === 'excel_teacher_markers') {
            $rules['value.*'] = ['required', 'string', Rule::enum(DeliveryType::class)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'value' => 'valor del parámetro',
            'value.*' => 'tipo de entrega',
            'description' => 'descripción',
        ];
    }
}
