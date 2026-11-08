<?php

namespace App\Models;

use App\Services\ImageService;
use App\Services\TextureDownloadService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property string|null $preview_url
 * @property string|null $thumbnail_url
 */
class Texture extends Model
{
    // ─── Possible statuses (same workflow as Family) ────────────────
    const STATUS_DRAFT          = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_PUBLISHED      = 'published';
    const STATUS_REJECTED       = 'rejected';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_REJECTED,
    ];

    // ─── Material types ─────────────────────────────────────────────
    const TYPE_PBR      = 'pbr';
    const TYPE_GLASS    = 'glass';
    const TYPE_METAL    = 'metal';
    const TYPE_WATER    = 'water';
    const TYPE_MIRROR   = 'mirror';
    const TYPE_EMISSIVE = 'emissive';

    /** Material type => label shown in the admin and the plugin. */
    const MATERIAL_TYPES = [
        self::TYPE_PBR      => 'Imagem (PBR)',
        self::TYPE_GLASS    => 'Vidro',
        self::TYPE_METAL    => 'Metal',
        self::TYPE_WATER    => 'Água',
        self::TYPE_MIRROR   => 'Espelho',
        self::TYPE_EMISSIVE => 'Emissivo (luz)',
    ];

    protected $fillable = [
        'user_id',
        'texture_category_id',
        'name',
        'slug',
        'description',
        'material_type',
        'base_color',
        'tile_width_mm',
        'tile_height_mm',
        'is_seamless',
        'shading_color',
        'preview_path',
        'version',
        'is_free',
        'is_featured',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'download_count',
    ];

    protected $casts = [
        'tile_width_mm'  => 'integer',
        'tile_height_mm' => 'integer',
        'is_seamless'    => 'boolean',
        'version'        => 'integer',
        'is_free'        => 'boolean',
        'is_featured'    => 'boolean',
        'download_count' => 'integer',
        'approved_at'    => 'datetime',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Texture $texture) {
            // Deleted one by one (not via the DB cascade) so TextureMap's
            // deleting hook removes each file from disk too.
            $texture->maps()->get()->each->delete();

            // Maps live in textures/{slug}/{resolution}/: drop the whole folder
            // so no empty directories are left behind.
            Storage::disk('local')->deleteDirectory('textures/' . $texture->slug);
            TextureDownloadService::forget($texture);

            if ($texture->preview_path) {
                app(ImageService::class)->deleteImageAndThumb($texture->preview_path, 'public');
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TextureCategory::class, 'texture_category_id');
    }

    public function maps(): HasMany
    {
        return $this->hasMany(TextureMap::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(TextureDownload::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function previewUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->preview_path ? $this->versionedUrl($this->preview_path) : null
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->preview_path) {
                    return null;
                }

                $thumb = dirname($this->preview_path) . '/thumbs/' . basename($this->preview_path);

                return Storage::disk('public')->exists($thumb)
                    ? $this->versionedUrl($thumb)
                    : $this->preview_url;
            }
        );
    }

    protected function materialTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => self::MATERIAL_TYPES[$this->material_type] ?? $this->material_type
        );
    }

    protected function tileSizeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->tile_width_mm . ' × ' . $this->tile_height_mm . ' mm'
        );
    }

    // ─── State checks ───────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function needsMaps(): bool
    {
        return $this->material_type === self::TYPE_PBR;
    }

    /**
     * Why the texture can't be published yet, or null when it can.
     * An image material needs at least a diffuse map.
     */
    public function publishBlocker(): ?string
    {
        if ($this->needsMaps() && ! $this->maps()->where('type', TextureMap::TYPE_DIFFUSE)->exists()) {
            return 'Envie pelo menos o mapa de cor (diffuse) antes de publicar.';
        }

        return null;
    }

    /** Resolutions that have at least a diffuse map, lowest first. */
    public function availableResolutions(): array
    {
        $present = $this->maps()
            ->where('type', TextureMap::TYPE_DIFFUSE)
            ->pluck('resolution')
            ->all();

        return array_values(array_intersect(TextureMap::RESOLUTIONS, $present));
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function incrementDownloads(): void
    {
        static::withoutTimestamps(fn() => $this->increment('download_count'));
    }

    /** Marks cached copies on clients as outdated. */
    public function bumpVersion(): void
    {
        $this->increment('version');
    }

    private function versionedUrl(string $path): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $disk->url($path);
        }

        return $disk->url($path) . '?v=' . $disk->lastModified($path);
    }
}
