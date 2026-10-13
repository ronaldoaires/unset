<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Traits\TracksViews; 

class Brand extends Model
{
    use TracksViews;
    
    protected $fillable = [
        'name',
        'slug',
        'website',
        'logo_path',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Remove the logo and its thumbnail from disk together with the record.
        static::deleting(function (Brand $brand) {
            if ($brand->logo_path) {
                app(ImageService::class)->deleteImageAndThumb($brand->logo_path, 'public');
            }
        });
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->logo_path
                ? $this->versionedUrl($this->logo_path)
                : null
        );
    }

    protected function logoThumbUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->logo_path) {
                    return null;
                }

                $thumbPath = dirname($this->logo_path) . '/thumbs/' . basename($this->logo_path);

                return Storage::disk('public')->exists($thumbPath)
                    ? $this->versionedUrl($thumbPath)
                    : $this->logo_url;
            }
        );
    }

    /**
     * Appends the file's last-modified timestamp so browsers fetch the new
     * image when it is replaced under the same filename.
     */
    private function versionedUrl(string $path): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $disk->url($path);
        }

        return $disk->url($path) . '?v=' . $disk->lastModified($path);
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function publishedFamilies(): HasMany
    {
        return $this->hasMany(Family::class)->where('status', 'published');
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('name');
    }
}
