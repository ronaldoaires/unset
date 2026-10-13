<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FamilyTag extends Model
{
    protected $fillable = [
        'family_id',
        'tag',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    // ─── Hooks ──────────────────────────────────────────────────────

    /**
     * Normalizes the tag to lowercase without accents before saving,
     * keeping search consistent and avoiding duplicates that differ
     * only by capitalization.
     */
    protected static function booted(): void
    {
        static::saving(function (FamilyTag $tag) {
            $tag->tag = mb_strtolower(
                Str::ascii($tag->tag)
            );
        });
    }
}
