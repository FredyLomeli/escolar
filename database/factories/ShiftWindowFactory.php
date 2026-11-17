<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\ShiftWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftWindow>
 */
class ShiftWindowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'day_of_week' => fake()->numberBetween(1, 5),
            'starts_at' => '08:00:00',
            'ends_at' => '14:00:00',
        ];
    }
}
