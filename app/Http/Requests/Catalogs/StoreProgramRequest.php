<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Program::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code', ''))),
            'name' => trim((string) $this->input('name', '')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', $this->codeUniqueRule()],
            'name' => ['required', 'string', 'max:150'],
            'modality_id' => ['required', 'integer', 'exists:modalities,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código de carrera',
            'name' => 'nombre de la carrera',
            'modality_id' => 'modalidad',
        ];
    }

    protected function codeUniqueRule(): Unique
    {
        return Rule::unique('programs', 'code');
    }
}
