<?php

namespace Database\Factories;

use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Shift;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentGroup>
 */
class StudentGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'curriculum_id' => Curriculum::factory(),
            'shift_id' => Shift::factory(),
            'entry_period_id' => AcademicPeriod::factory(),
            'label' => null,
            'status' => StudentGroupStatus::Active,
        ];
    }
}
