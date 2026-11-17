<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Shift::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::slug(trim((string) $this->input('code', ''))),
            'name' => trim((string) $this->input('name', '')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'modality_id' => ['required', 'integer', 'exists:modalities,id'],
            'code' => ['required', 'string', 'max:50', $this->codeUniqueRule()],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'modality_id' => 'modalidad',
            'code' => 'código del turno',
            'name' => 'nombre del turno',
        ];
    }

    protected function codeUniqueRule(): Unique
    {
        return Rule::unique('shifts', 'code');
    }
}
