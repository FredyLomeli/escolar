<?php

namespace App\Http\Requests\Catalogs;

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $curriculum = $this->route('curriculum');

        return $curriculum instanceof Curriculum
            && ($this->user()?->can('update', $curriculum) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $displayName = trim((string) $this->input('display_name', ''));

        $this->merge([
            'weekly_hours' => $this->input('weekly_hours') !== '' && $this->input('weekly_hours') !== null
                ? $this->input('weekly_hours')
                : null,
            'display_name' => $displayName !== '' ? $displayName : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Curriculum $curriculum */
        $curriculum = $this->route('curriculum');

        return [
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                Rule::unique('curriculum_courses', 'course_id')->where('curriculum_id', $curriculum->id),
            ],
            ...$this->sharedRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.unique' => 'Esta materia ya forma parte del plan de estudios.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'course_id' => 'materia',
            'term_number' => 'cuatrimestre',
            'kind' => 'tipo de materia',
            'default_delivery' => 'entrega por defecto',
            'weekly_hours' => 'horas semanales',
            'display_name' => 'nombre comercial en el plan',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function sharedRules(): array
    {
        return [
            'term_number' => ['required', 'integer', 'between:1,9'],
            'kind' => ['required', 'string', Rule::enum(CourseKind::class)],
            'default_delivery' => ['required', 'string', Rule::enum(DeliveryType::class)],
            'weekly_hours' => ['nullable', 'numeric', 'min:0.5', 'max:40'],
            'display_name' => ['nullable', 'string', 'max:200'],
        ];
    }
}
