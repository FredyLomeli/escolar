<?php

namespace App\Models;

use App\Enums\AcademicPeriodStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property AcademicPeriodStatus $status
 * @property Carbon|CarbonImmutable $starts_on
 * @property Carbon|CarbonImmutable $ends_on
 */
#[Fillable(['code', 'name', 'year', 'term_of_year', 'starts_on', 'ends_on', 'weeks', 'status'])]
class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => AcademicPeriodStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'term_of_year' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'weeks' => 'integer',
            'status' => AcademicPeriodStatus::class,
        ];
    }

    /**
     * @return HasMany<StudentGroup, $this>
     */
    public function entryStudentGroups(): HasMany
    {
        return $this->hasMany(StudentGroup::class, 'entry_period_id');
    }

    /**
     * @return HasMany<GroupTerm, $this>
     */
    public function groupTerms(): HasMany
    {
        return $this->hasMany(GroupTerm::class);
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @return HasMany<TeacherAvailability, $this>
     */
    public function teacherAvailabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }
}
