<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherCourseRequest extends FormRequest
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
        /** @var Teacher $teacher */
        $teacher = $this->route('teacher');

        return [
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                Rule::unique('teacher_courses', 'course_id')->where('teacher_id', $teacher->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.unique' => 'Esta materia ya está asignada al perfil del docente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'course_id' => 'materia',
        ];
    }
}
