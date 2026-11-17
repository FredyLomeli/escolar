<?php

namespace Database\Factories;

use App\Models\ReservedBlock;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservedBlock>
 */
class ReservedBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'name' => 'Inglés',
            'day_of_week' => fake()->numberBetween(1, 4),
            'starts_at' => '12:30:00',
            'ends_at' => '14:00:00',
        ];
    }
}
