<?php

namespace Database\Factories;

use App\Enums\ModalityCode;
use App\Models\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modality>
 */
class ModalityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(ModalityCode::cases()),
            'name' => 'Modalidad',
        ];
    }

    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => ModalityCode::Regular,
            'name' => ModalityCode::Regular->label(),
        ]);
    }

    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => ModalityCode::Executive,
            'name' => ModalityCode::Executive->label(),
        ]);
    }
}
