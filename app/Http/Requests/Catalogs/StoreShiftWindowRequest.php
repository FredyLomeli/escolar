<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shift = $this->route('shift');

        return $shift instanceof Shift
            && ($this->user()?->can('update', $shift) ?? false);
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
        ];
    }
}
