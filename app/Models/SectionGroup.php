<?php

namespace App\Models;

use Database\Factories\SectionGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'group_term_id'])]
class SectionGroup extends Model
{
    /** @use HasFactory<SectionGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<GroupTerm, $this>
     */
    public function groupTerm(): BelongsTo
    {
        return $this->belongsTo(GroupTerm::class);
    }
}
