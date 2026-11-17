<?php

namespace Database\Factories;

use App\Models\Modality;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'name' => fake()->words(3, true),
            'modality_id' => fn (): int => Modality::query()->value('id') ?? Modality::factory()->regular()->create()->id,
            'active' => true,
        ];
    }
}
