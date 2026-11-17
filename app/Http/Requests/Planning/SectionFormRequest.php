<?php

namespace App\Http\Requests\Planning;

use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Models\AcademicPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class SectionFormRequest extends FormRequest
{
    abstract protected function targetPeriodId(): int;

    protected function ignoredSectionId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        $crn = trim((string) $this->input('crn', ''));
        $notes = trim((string) $this->input('notes', ''));
        $teacherId = $this->input('teacher_id');
        $studentsOverride = $this->input('students_override');

        $rawMeetings = $this->input('meetings', []);
        $normalizedMeetings = [];

        if (is_array($rawMeetings)) {
            foreach ($rawMeetings as $meeting) {
                if (! is_array($meeting)) {
                    continue;
                }

                $classroomId = $meeting['classroom_id'] ?? null;

                $normalizedMeetings[] = [
                    'day_of_week' => $meeting['day_of_week'] ?? null,
                    'starts_at' => substr(trim((string) ($meeting['starts_at'] ?? '')), 0, 5),
                    'ends_at' => substr(trim((string) ($meeting['ends_at'] ?? '')), 0, 5),
                    'classroom_id' => ($classroomId === '' || $classroomId === 0 || $classroomId === '0') ? null : $classroomId,
                ];
            }
        }

        $this->merge([
            'crn' => $crn !== '' ? $crn : null,
            'notes' => $notes !== '' ? $notes : null,
            'teacher_id' => ($teacherId === '' || $teacherId === 0 || $teacherId === '0') ? null : $teacherId,
            'students_override' => ($studentsOverride === '' || $studentsOverride === null) ? null : $studentsOverride,
            'meetings' => $normalizedMeetings,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function baseSectionRules(): array
    {
        $crnUnique = Rule::unique('sections', 'crn')
            ->where('academic_period_id', $this->targetPeriodId());

        $ignoreId = $this->ignoredSectionId();
        if ($ignoreId !== null) {
            $crnUnique->ignore($ignoreId);
        }

        return [
            'crn' => ['nullable', 'string', 'max:32', $crnUnique],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'delivery' => ['required', 'string', Rule::enum(DeliveryType::class)],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'start_week' => ['required', 'integer', 'min:1'],
            'end_week' => ['required', 'integer', 'gte:start_week'],
            'students_override' => ['nullable', 'integer', 'min:0', 'max:500'],
            'status' => ['required', 'string', Rule::enum(SectionStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'meetings' => ['present', 'array'],
            'meetings.*.day_of_week' => ['required', 'integer', 'between:1,6'],
            'meetings.*.starts_at' => ['required', 'date_format:H:i'],
            'meetings.*.ends_at' => ['required', 'date_format:H:i'],
            'meetings.*.classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $period = AcademicPeriod::query()->find($this->targetPeriodId());
                $endWeek = $this->integer('end_week');

                if ($period !== null && $endWeek > $period->weeks) {
                    $validator->errors()->add(
                        'end_week',
                        "La semana final no puede exceder las {$period->weeks} semanas del periodo.",
                    );
                }

                $meetings = $this->input('meetings', []);
                if (! is_array($meetings)) {
                    return;
                }

                foreach ($meetings as $index => $meeting) {
                    if (! is_array($meeting)) {
                        continue;
                    }

                    $startsAt = (string) ($meeting['starts_at'] ?? '');
                    $endsAt = (string) ($meeting['ends_at'] ?? '');

                    if ($startsAt !== '' && $endsAt !== '' && $endsAt <= $startsAt) {
                        $validator->errors()->add(
                            "meetings.{$index}.ends_at",
                            'La hora de fin debe ser posterior a la hora de inicio.',
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'academic_period_id' => 'periodo académico',
            'group_term_id' => 'grupo del periodo',
            'course_id' => 'materia',
            'crn' => 'CRN',
            'teacher_id' => 'docente',
            'delivery' => 'entrega',
            'shift_id' => 'turno',
            'start_week' => 'semana inicial',
            'end_week' => 'semana final',
            'students_override' => 'alumnos ajustados',
            'status' => 'estado',
            'notes' => 'notas',
            'meetings.*.day_of_week' => 'día de la semana',
            'meetings.*.starts_at' => 'hora de inicio',
            'meetings.*.ends_at' => 'hora de fin',
            'meetings.*.classroom_id' => 'aula',
        ];
    }
}
