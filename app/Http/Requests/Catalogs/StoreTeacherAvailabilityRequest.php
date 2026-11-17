<?php

namespace App\Http\Requests\Catalogs;

use App\Enums\AvailabilityType;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof Teacher
            && ($this->user()?->can('update', $teacher) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'type' => ['required', 'string', Rule::enum(AvailabilityType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'days_of_week' => 'días de la semana',
            'days_of_week.*' => 'día de la semana',
            'starts_at' => 'hora de inicio',
            'ends_at' => 'hora de fin',
            'type' => 'tipo de disponibilidad',
        ];
    }
}
