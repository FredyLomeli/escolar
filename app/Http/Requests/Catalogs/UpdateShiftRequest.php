<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Shift;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateShiftRequest extends StoreShiftRequest
{
    public function authorize(): bool
    {
        $shift = $this->route('shift');

        return $shift instanceof Shift
            && ($this->user()?->can('update', $shift) ?? false);
    }

    protected function codeUniqueRule(): Unique
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');

        return Rule::unique('shifts', 'code')->ignore($shift->id);
    }
}
