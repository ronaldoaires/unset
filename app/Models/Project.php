<?php

namespace App\Models;

use App\Services\ImageService;
use App\Traits\CleansRichContentImages;
use App\Traits\HasPublicImages;
use App\Traits\TracksViews;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A portfolio entry: a real job that proves one of the services.
 */
class Project extends Model
{
    use CleansRichContentImages;
    use HasPublicImages;
    use TracksViews;

    protected $fillable = [
        'service_id',
        'title',
        'slug',
        'summary',
        'body',
        'cover_path',
        'location',
        'year',
        'built_area',
        'external_url',
        'client_name',
        'seo_title',
        'seo_description',
        'is_active',
        'is_featured',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'year'         => 'integer',
        'built_area'   => 'decimal:2',
        'is_active'    => 'boolean',
        'is_featured'  => 'boolean',
        'sort_order'   => 'integer',
        'published_at' => 'datetime',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // First time a project goes live, stamp the publication date (used by the sitemap and schema).
        static::saving(function (Project $project) {
            if ($project->is_active && ! $project->published_at) {
                $project->published_at = now();
            }
        });

        static::deleting(function (Project $project) {
            // Delete each image as a model so its own hook removes the files.
            $project->images()->get()->each->delete();

            if ($project->cover_path) {
                app(ImageService::class)->deleteImageAndThumb($project->cover_path, 'public');
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

    protected function metaTitle(): Attribute
    {
        return Attribute::make(get: fn () => $this->seo_title ?: $this->title);
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(get: fn () => $this->seo_description ?: $this->summary);
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->latest('published_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
