<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Teacher;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateTeacherRequest extends StoreTeacherRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof Teacher
            && ($this->user()?->can('update', $teacher) ?? false);
    }

    protected function employeeIdUniqueRule(): Unique
    {
        /** @var Teacher $teacher */
        $teacher = $this->route('teacher');

        return Rule::unique('teachers', 'employee_id')->ignore($teacher->id);
    }
}
