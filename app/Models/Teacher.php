<?php

namespace App\Models;

use App\Enums\TeacherOrigin;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TeacherOrigin $origin
 */
#[Fillable(['employee_id', 'full_name', 'email', 'origin', 'max_weekly_hours', 'active'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'origin' => TeacherOrigin::Campus->value,
        'active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin' => TeacherOrigin::class,
            'max_weekly_hours' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TeacherAvailability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    /**
     * @return HasMany<TeacherCourse, $this>
     */
    public function teacherCourses(): HasMany
    {
        return $this->hasMany(TeacherCourse::class);
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_courses')
            ->withPivot('source')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
