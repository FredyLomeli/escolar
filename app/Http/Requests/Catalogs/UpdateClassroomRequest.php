<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Classroom;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateClassroomRequest extends StoreClassroomRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom instanceof Classroom
            && ($this->user()?->can('update', $classroom) ?? false);
    }

    protected function codeUniqueRule(): Unique
    {
        /** @var Classroom $classroom */
        $classroom = $this->route('classroom');

        return Rule::unique('classrooms', 'code')->ignore($classroom->id);
    }
}
