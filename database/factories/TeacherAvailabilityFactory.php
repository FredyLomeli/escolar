<?php

namespace Database\Factories;

use App\Enums\AvailabilityType;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAvailability>
 */
class TeacherAvailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'academic_period_id' => null,
            'day_of_week' => fake()->numberBetween(1, 6),
            'starts_at' => '08:00:00',
            'ends_at' => '14:00:00',
            'type' => AvailabilityType::Available,
        ];
    }
}
