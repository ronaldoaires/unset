<?php

namespace App\Models;

use App\Enums\ServiceArea;
use App\Services\ImageService;
use App\Support\ShowcaseMenu;
use App\Traits\CleansRichContentImages;
use App\Traits\HasPublicImages;
use App\Traits\TracksViews;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use CleansRichContentImages;
    use HasPublicImages;
    use TracksViews;

    protected $fillable = [
        'area',
        'name',
        'slug',
        'headline',
        'summary',
        'body',
        'cover_path',
        'icon',
        'deliverables',
        'faq',
        'seo_title',
        'seo_description',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'area'         => ServiceArea::class,
        'deliverables' => 'array',
        'faq'          => 'array',
        'is_active'    => 'boolean',
        'is_featured'  => 'boolean',
        'sort_order'   => 'integer',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // The header and footer menus list the active services.
        static::saved(fn () => ShowcaseMenu::forget());
        static::deleted(fn () => ShowcaseMenu::forget());

        // Projects are protected by a restrictive FK; only the cover is ours to clean up.
        static::deleting(function (Service $service) {
            if ($service->cover_path) {
                app(ImageService::class)->deleteImageAndThumb($service->cover_path, 'public');
            }
        });
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function coverUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->publicImageUrl($this->cover_path));
    }

    protected function coverThumbUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->publicThumbUrl($this->cover_path));
    }

    /**
     * Heroicons outline name to render, falling back to the area's icon.
     */
    protected function iconName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon ?: config('showcase.areas.' . $this->area?->value . '.icon', 'briefcase')
        );
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::make(get: fn () => $this->seo_title ?: $this->name);
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(get: fn () => $this->seo_description ?: $this->summary);
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function activeProjects(): HasMany
    {
        return $this->projects()->active();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeInArea(Builder $query, ServiceArea $area): Builder
    {
        return $query->where('area', $area);
    }
}
