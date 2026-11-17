<?php

namespace App\Models;

use App\Enums\AvailabilityType;
use Database\Factories\TeacherAvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AvailabilityType $type
 */
#[Fillable(['teacher_id', 'academic_period_id', 'day_of_week', 'starts_at', 'ends_at', 'type'])]
class TeacherAvailability extends Model
{
    /** @use HasFactory<TeacherAvailabilityFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => AvailabilityType::Available->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'type' => AvailabilityType::class,
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
