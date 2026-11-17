<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Classroom::class) ?? false;
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
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código de aula',
            'name' => 'nombre del aula',
            'capacity' => 'capacidad',
        ];
    }

    protected function codeUniqueRule(): Unique
    {
        return Rule::unique('classrooms', 'code');
    }
}
