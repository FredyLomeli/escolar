<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_code' => strtoupper(fake()->lexify('????')),
            'course_code' => strtoupper(fake()->unique()->bothify('???##')),
            'name' => fake()->words(3, true),
            'active' => true,
        ];
    }
}
