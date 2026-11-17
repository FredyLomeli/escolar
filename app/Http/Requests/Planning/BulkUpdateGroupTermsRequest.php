<?php

namespace App\Http\Requests\Planning;

use App\Models\GroupTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateGroupTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', GroupTerm::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $periodId = (int) $this->input('academic_period_id');

        return [
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('group_terms', 'id')->where('academic_period_id', $periodId),
            ],
            'items.*.student_count' => ['required', 'integer', 'min:0', 'max:500'],
            'items.*.student_count_confirmed' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'academic_period_id' => 'periodo académico',
            'items.*.student_count' => 'número de alumnos',
            'items.*.student_count_confirmed' => 'confirmación de matrícula',
        ];
    }
}
