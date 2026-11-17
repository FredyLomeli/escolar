<?php

namespace Database\Factories;

use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_period_id' => AcademicPeriod::factory(),
            'course_id' => Course::factory(),
            'crn' => null,
            'teacher_id' => null,
            'delivery' => DeliveryType::InPerson,
            'shift_id' => Shift::factory(),
            'start_week' => 1,
            'end_week' => 14,
            'students_override' => null,
            'status' => SectionStatus::Draft,
            'notes' => null,
        ];
    }
}
