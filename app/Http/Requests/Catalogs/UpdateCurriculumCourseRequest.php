<?php

namespace App\Http\Requests\Catalogs;

use App\Models\CurriculumCourse;

class UpdateCurriculumCourseRequest extends StoreCurriculumCourseRequest
{
    public function authorize(): bool
    {
        $curriculumCourse = $this->route('curriculumCourse');

        if (! $curriculumCourse instanceof CurriculumCourse) {
            return false;
        }

        $curriculumCourse->loadMissing('curriculum');

        return $this->user()?->can('update', $curriculumCourse->curriculum) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->sharedRules();
    }
}
