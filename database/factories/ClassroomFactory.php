<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = (string) fake()->unique()->numberBetween(101, 999);

        return [
            'code' => $code,
            'name' => "Aula {$code}",
            'capacity' => fake()->randomElement([20, 25, 30, 35, 40]),
            'active' => true,
        ];
    }
}
