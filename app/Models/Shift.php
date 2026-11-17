<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['modality_id', 'code', 'name', 'active'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Modality, $this>
     */
    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    /**
     * @return HasMany<ShiftWindow, $this>
     */
    public function windows(): HasMany
    {
        return $this->hasMany(ShiftWindow::class);
    }

    /**
     * @return HasMany<ReservedBlock, $this>
     */
    public function reservedBlocks(): HasMany
    {
        return $this->hasMany(ReservedBlock::class);
    }
}
