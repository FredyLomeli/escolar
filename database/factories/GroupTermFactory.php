<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupTerm>
 */
class GroupTermFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_group_id' => StudentGroup::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'term_number' => fake()->numberBetween(1, 9),
            'student_count' => fake()->numberBetween(15, 28),
            'student_count_confirmed' => false,
        ];
    }
}
