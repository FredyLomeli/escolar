<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $curriculum = $this->route('curriculum');

        return $curriculum instanceof Curriculum
            && ($this->user()?->can('update', $curriculum) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'version' => trim((string) $this->input('version', '')),
            'valid_from' => $this->input('valid_from') ?: null,
            'valid_to' => $this->input('valid_to') ?: null,
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
            'version' => [
                'required',
                'string',
                'max:50',
                Rule::unique('curricula', 'version')
                    ->where('program_id', $curriculum->program_id)
                    ->ignore($curriculum->id),
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => [
                'nullable',
                'date',
                Rule::when($this->filled('valid_from'), ['after_or_equal:valid_from']),
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
        ];
    }
}
