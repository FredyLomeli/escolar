<?php

namespace Database\Factories;

use App\Models\Modality;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modality_id' => fn (): int => Modality::query()->value('id') ?? Modality::factory()->regular()->create()->id,
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }
}
