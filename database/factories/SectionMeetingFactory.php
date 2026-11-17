<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Section;
use App\Models\SectionMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionMeeting>
 */
class SectionMeetingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'day_of_week' => fake()->numberBetween(1, 5),
            'starts_at' => '08:00:00',
            'ends_at' => '10:00:00',
            'classroom_id' => Classroom::factory(),
        ];
    }
}
