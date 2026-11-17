<?php

namespace App\Models;

use Database\Factories\GroupTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_group_id', 'academic_period_id', 'term_number', 'student_count', 'student_count_confirmed'])]
class GroupTerm extends Model
{
    /** @use HasFactory<GroupTermFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'student_count' => 0,
        'student_count_confirmed' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
            'student_count' => 'integer',
            'student_count_confirmed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<StudentGroup, $this>
     */
    public function studentGroup(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class);
    }

    /**
     * @return BelongsTo<AcademicPeriod, $this>
     */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /**
     * @return HasMany<SectionGroup, $this>
     */
    public function sectionGroups(): HasMany
    {
        return $this->hasMany(SectionGroup::class);
    }

    /**
     * @return BelongsToMany<Section, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_groups')
            ->withTimestamps();
    }
}
