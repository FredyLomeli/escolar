<?php

namespace Database\Factories;

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumCourse>
 */
class CurriculumCourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_id' => Curriculum::factory(),
            'course_id' => Course::factory(),
            'term_number' => fake()->numberBetween(1, 9),
            'kind' => CourseKind::Regular,
            'default_delivery' => DeliveryType::InPerson,
            'weekly_hours' => 4.0,
            'display_name' => null,
        ];
    }
}
