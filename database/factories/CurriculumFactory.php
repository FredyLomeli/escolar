<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curriculum>
 */
class CurriculumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'version' => (string) fake()->unique()->numberBetween(2020, 2099),
            'valid_from' => now()->startOfYear(),
            'valid_to' => null,
            'active' => true,
        ];
    }
}
