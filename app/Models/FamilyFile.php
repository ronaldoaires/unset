<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

/**
 * @property string|null $url
 * @property string $formatted_size
 */
class FamilyFile extends Model
{
    // ─── Supported file types ───────────────────────────────────────
    const TYPE_RFA   = 'rfa';
    const TYPE_IMAGE = 'image';
    const TYPE_THUMB = 'thumb';

    const TYPES = [
        self::TYPE_RFA,
        self::TYPE_IMAGE,
        self::TYPE_THUMB,
    ];

    protected $fillable = [
        'family_id',
        'type',
        'disk',
        'path',
        'original_name',
        'size_bytes',
        'mime_type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size_bytes' => 'integer',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Remove the physical file together with its record. Images go
        // through ImageService so the generated thumbnail is removed too.
        static::deleting(function (FamilyFile $file) {
            $disk = $file->disk ?? 'public';

            if ($file->type === self::TYPE_IMAGE) {
                app(ImageService::class)->deleteImageAndThumb($file->path, $disk);

                return;
            }

            Storage::disk($disk)->delete($file->path);
        });
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    // ─── Accessors (Laravel 10+ syntax) ─────────────────────────────

    /**
     * Public URL of the file, respecting the configured disk.
     * Usage: $file->url
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->path) {
                    return null;
                }

                $url = Storage::disk($this->disk ?? 'public')->url($this->path);

                // A replaced cover keeps the same filename, so the version query
                // string makes browsers drop the cached copy.
                return $this->updated_at
                    ? $url . '?v=' . $this->updated_at->timestamp
                    : $url;
            }
        );
    }

    /**
     * Formatted size for display.
     * Usage: $file->formatted_size  →  "2.4 MB"
     */
    protected function formattedSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->size_bytes) {
                    return '—';
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $bytes = $this->size_bytes;
                $i     = 0;

                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }

                return round($bytes, 1) . ' ' . $units[$i];
            }
        );
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeRfa($query)
    {
        return $query->where('type', self::TYPE_RFA);
    }

    public function scopeImages($query)
    {
        return $query->where('type', self::TYPE_IMAGE);
    }

    public function scopeThumbs($query)
    {
        return $query->where('type', self::TYPE_THUMB);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
