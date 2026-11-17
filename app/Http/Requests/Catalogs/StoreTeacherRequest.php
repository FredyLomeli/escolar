<?php

namespace App\Http\Requests\Catalogs;

use App\Enums\TeacherOrigin;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Teacher::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $employeeId = trim((string) $this->input('employee_id', ''));
        if ($employeeId !== '' && ctype_digit($employeeId) && strlen($employeeId) < 8) {
            $employeeId = str_pad($employeeId, 8, '0', STR_PAD_LEFT);
        }

        $email = trim((string) $this->input('email', ''));
        $maxHours = $this->input('max_weekly_hours');

        $this->merge([
            'employee_id' => $employeeId,
            'full_name' => trim((string) $this->input('full_name', '')),
            'email' => $email !== '' ? strtolower($email) : null,
            'max_weekly_hours' => $maxHours !== '' && $maxHours !== null ? $maxHours : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'max:32', $this->employeeIdUniqueRule()],
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'origin' => ['required', 'string', Rule::enum(TeacherOrigin::class)],
            'max_weekly_hours' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'ID docente',
            'full_name' => 'nombre completo',
            'email' => 'correo electrónico',
            'origin' => 'origen',
            'max_weekly_hours' => 'horas semanales máximas',
        ];
    }

    protected function employeeIdUniqueRule(): Unique
    {
        return Rule::unique('teachers', 'employee_id');
    }
}
