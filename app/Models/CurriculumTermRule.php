<?php

namespace App\Models;

use Database\Factories\CurriculumTermRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['curriculum_id', 'term_number', 'expected_courses'])]
class CurriculumTermRule extends Model
{
    /** @use HasFactory<CurriculumTermRuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
            'expected_courses' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Curriculum, $this>
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
