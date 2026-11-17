<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            && ($this->user()?->can('create', Curriculum::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'version' => trim((string) $this->input('version', '')),
            'valid_from' => $this->input('valid_from') ?: null,
            'valid_to' => $this->input('valid_to') ?: null,
            'clone_from_curriculum_id' => $this->input('clone_from_curriculum_id') ?: null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Program $program */
        $program = $this->route('program');

        return [
            'version' => [
                'required',
                'string',
                'max:50',
                Rule::unique('curricula', 'version')->where('program_id', $program->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => [
                'nullable',
                'date',
                Rule::when($this->filled('valid_from'), ['after_or_equal:valid_from']),
            ],
            'clone_from_curriculum_id' => [
                'nullable',
                'integer',
                Rule::exists('curricula', 'id')->where('program_id', $program->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'version' => 'versión del plan',
            'valid_from' => 'vigencia desde',
            'valid_to' => 'vigencia hasta',
            'clone_from_curriculum_id' => 'plan origen a clonar',
        ];
    }
}
