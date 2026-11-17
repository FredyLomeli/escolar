<?php

namespace App\Http\Requests\Planning;

use App\Models\Program;
use App\Models\Shift;
use App\Models\StudentGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StudentGroup::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $label = trim((string) $this->input('label', ''));

        $this->merge([
            'label' => $label !== '' ? $label : null,
            'student_count_confirmed' => (bool) $this->boolean('student_count_confirmed'),
            'generate_sections' => (bool) $this->boolean('generate_sections', true),
            'suggest_teachers' => (bool) $this->boolean('suggest_teachers', true),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'curriculum_id' => [
                'required',
                'integer',
                Rule::exists('curricula', 'id')->where('program_id', $this->input('program_id')),
            ],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'term_number' => ['required', 'integer', 'between:1,9'],
            'label' => ['nullable', 'string', 'max:100'],
            'student_count' => ['required', 'integer', 'min:0', 'max:500'],
            'student_count_confirmed' => ['required', 'boolean'],
            'generate_sections' => ['required', 'boolean'],
            'suggest_teachers' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $programId = $this->integer('program_id');
                $shiftId = $this->integer('shift_id');

                if ($programId <= 0 || $shiftId <= 0) {
                    return;
                }

                $program = Program::query()->find($programId);
                $shift = Shift::query()->find($shiftId);

                if ($program !== null && $shift !== null && $program->modality_id !== $shift->modality_id) {
                    $validator->errors()->add('shift_id', 'El turno seleccionado debe corresponder a la modalidad de la carrera.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'academic_period_id' => 'periodo académico',
            'program_id' => 'carrera',
            'curriculum_id' => 'plan de estudios',
            'shift_id' => 'turno',
            'term_number' => 'cuatrimestre',
            'label' => 'etiqueta del grupo',
            'student_count' => 'número de alumnos',
        ];
    }
}
