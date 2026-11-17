<?php

namespace Database\Factories;

use App\Enums\TeacherCourseSource;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeacherCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherCourse>
 */
class TeacherCourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'course_id' => Course::factory(),
            'source' => TeacherCourseSource::Manual,
        ];
    }
}
