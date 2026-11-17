<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Course;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateCourseRequest extends StoreCourseRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course
            && ($this->user()?->can('update', $course) ?? false);
    }

    protected function pairUniqueRule(): Unique
    {
        /** @var Course $course */
        $course = $this->route('course');

        return Rule::unique('courses', 'course_code')
            ->where('subject_code', $this->input('subject_code'))
            ->ignore($course->id);
    }
}
