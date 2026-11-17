<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Program;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateProgramRequest extends StoreProgramRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            && ($this->user()?->can('update', $program) ?? false);
    }

    protected function codeUniqueRule(): Unique
    {
        /** @var Program $program */
        $program = $this->route('program');

        return Rule::unique('programs', 'code')->ignore($program->id);
    }
}
