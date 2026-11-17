<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject_code' => strtoupper(trim((string) $this->input('subject_code', ''))),
            'course_code' => strtoupper(trim((string) $this->input('course_code', ''))),
            'name' => trim((string) $this->input('name', '')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject_code' => ['required', 'string', 'max:16'],
            'course_code' => ['required', 'string', 'max:16', $this->pairUniqueRule()],
            'name' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_code.unique' => 'Ya existe una materia registrada con esta pareja SUBJ + CRSE.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject_code' => 'SUBJ',
            'course_code' => 'CRSE',
            'name' => 'nombre de la materia',
        ];
    }

    protected function pairUniqueRule(): Unique
    {
        return Rule::unique('courses', 'course_code')
            ->where('subject_code', $this->input('subject_code'));
    }
}
