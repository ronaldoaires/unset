<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TracksViews; 

class Category extends Model
{

    use TracksViews;
    
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
    }

    public function activeSubcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Families belong to a category directly via category_id
     * (subcategory is optional, not the source of truth).
     */
    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function publishedFamilies(): HasMany
    {
        return $this->hasMany(Family::class)
            ->where('status', Family::STATUS_PUBLISHED);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
