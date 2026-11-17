<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Curriculum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCurriculumTermRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $curriculum = $this->route('curriculum');

        return $curriculum instanceof Curriculum
            && ($this->user()?->can('update', $curriculum) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_courses' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expected_courses' => 'materias esperadas',
        ];
    }
}
