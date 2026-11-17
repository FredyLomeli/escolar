<?php

namespace App\Http\Requests\Planning;

use App\Models\Section;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends SectionFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Section::class) ?? false;
    }

    protected function targetPeriodId(): int
    {
        return $this->integer('academic_period_id');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
                'group_term_id' => [
                    'required',
                    'integer',
                    Rule::exists('group_terms', 'id')->where('academic_period_id', $this->targetPeriodId()),
                ],
                'course_id' => ['required', 'integer', 'exists:courses,id'],
            ],
            $this->baseSectionRules(),
        );
    }
}
