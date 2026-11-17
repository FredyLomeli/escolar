<?php

namespace App\Models;

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use Database\Factories\CurriculumCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CourseKind $kind
 * @property DeliveryType $default_delivery
 */
#[Fillable(['curriculum_id', 'course_id', 'term_number', 'kind', 'default_delivery', 'weekly_hours', 'display_name'])]
class CurriculumCourse extends Model
{
    /** @use HasFactory<CurriculumCourseFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'kind' => CourseKind::Regular->value,
        'default_delivery' => DeliveryType::InPerson->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
            'kind' => CourseKind::class,
            'default_delivery' => DeliveryType::class,
            'weekly_hours' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Curriculum, $this>
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
