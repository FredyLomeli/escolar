<?php

namespace App\Http\Requests\Planning;

use App\Models\AcademicPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicPeriod::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => trim((string) $this->input('code', '')),
            'name' => trim((string) $this->input('name', '')),
            'source_period_id' => $this->input('source_period_id') ?: null,
            'copy_meetings' => (bool) $this->boolean('copy_meetings'),
            'suggest_teachers' => (bool) $this->boolean('suggest_teachers', true),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('academic_periods', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'term_of_year' => ['required', 'integer', 'between:1,3'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'weeks' => ['required', 'integer', 'between:1,30'],
            'source_period_id' => ['nullable', 'integer', 'exists:academic_periods,id'],
            'copy_meetings' => ['required', 'boolean'],
            'suggest_teachers' => ['required', 'boolean'],
            'student_count_overrides' => ['nullable', 'array'],
            'student_count_overrides.*' => ['required', 'integer', 'min:0', 'max:500'],
            'excluded_group_ids' => ['nullable', 'array'],
            'excluded_group_ids.*' => ['required', 'integer', 'exists:student_groups,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código del periodo',
            'name' => 'nombre del periodo',
            'year' => 'año',
            'term_of_year' => 'cuatrimestre del año',
            'starts_on' => 'fecha de inicio',
            'ends_on' => 'fecha de fin',
            'weeks' => 'duración en semanas',
            'source_period_id' => 'periodo origen',
            'student_count_overrides.*' => 'alumnos estimados',
        ];
    }
}
