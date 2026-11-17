<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\CurriculumTermRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumTermRule>
 */
class CurriculumTermRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_id' => Curriculum::factory(),
            'term_number' => fake()->numberBetween(1, 9),
            'expected_courses' => 5,
        ];
    }
}
