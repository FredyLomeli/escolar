<?php

namespace App\Models;

use App\Enums\StudentGroupStatus;
use Database\Factories\StudentGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property StudentGroupStatus $status
 */
#[Fillable(['program_id', 'curriculum_id', 'shift_id', 'entry_period_id', 'label', 'status'])]
class StudentGroup extends Model
{
    /** @use HasFactory<StudentGroupFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => StudentGroupStatus::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudentGroupStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Curriculum, $this>
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return BelongsTo<AcademicPeriod, $this>
     */
    public function entryPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'entry_period_id');
    }

    /**
     * @return HasMany<GroupTerm, $this>
     */
    public function groupTerms(): HasMany
    {
        return $this->hasMany(GroupTerm::class);
    }
}
