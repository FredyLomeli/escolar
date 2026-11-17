<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property DeliveryType $delivery
 * @property SectionStatus $status
 */
#[Fillable([
    'academic_period_id',
    'course_id',
    'crn',
    'teacher_id',
    'delivery',
    'shift_id',
    'start_week',
    'end_week',
    'students_override',
    'status',
    'notes',
])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'delivery' => DeliveryType::InPerson->value,
        'status' => SectionStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery' => DeliveryType::class,
            'start_week' => 'integer',
            'end_week' => 'integer',
            'students_override' => 'integer',
            'status' => SectionStatus::class,
        ];
    }

    public function studentCount(): int
    {
        if ($this->students_override !== null) {
            return $this->students_override;
        }

        return (int) $this->groupTerms->sum('student_count');
    }

    public function isCompacted(): bool
    {
        return $this->groupTerms->count() > 1;
    }

    /**
     * @return BelongsTo<AcademicPeriod, $this>
     */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return HasMany<SectionGroup, $this>
     */
    public function sectionGroups(): HasMany
    {
        return $this->hasMany(SectionGroup::class);
    }

    /**
     * @return BelongsToMany<GroupTerm, $this>
     */
    public function groupTerms(): BelongsToMany
    {
        return $this->belongsToMany(GroupTerm::class, 'section_groups')
            ->withTimestamps();
    }

    /**
     * @return HasMany<SectionMeeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(SectionMeeting::class);
    }
}
