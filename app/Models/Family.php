<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\TracksViews;
use Illuminate\Support\Str;

class Family extends Model
{
    use TracksViews;

    // ─── Possible statuses ──────────────────────────────────────────
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

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'revit_version',
        'revit_category',
        'is_parametric',
        'is_free',
        'price',
        'is_featured',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'download_count',
        'view_count',
    ];

    protected $casts = [
        'revit_version'  => 'integer',
        'is_parametric'  => 'boolean',
        'is_free'        => 'boolean',
        'is_featured'    => 'boolean',
        'price'          => 'decimal:2',
        'download_count' => 'integer',
        'view_count'     => 'integer',
        'last_viewed_at' => 'datetime',
        'approved_at'    => 'datetime',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Family $family) {
            // Purchases are financial history: a family that was ever bought
            // can't be deleted. Returning false cancels the delete and Filament
            // shows the action's failure notification.
            if ($family->purchases()->exists()) {
                return false;
            }

            // Each file is deleted as a model (not via the DB cascade) so
            // FamilyFile's deleting hook removes it from disk too.
            $family->files()->get()->each->delete();
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

    public function purchases(): HasMany
    {
        return $this->hasMany(FamilyPurchase::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    /**
     * Direct category — always present.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Subcategory — optional (nullable).
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(FamilyFile::class);
    }

    public function rfaFiles(): HasMany
    {
        return $this->hasMany(FamilyFile::class)
            ->where('type', FamilyFile::TYPE_RFA);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(FamilyFile::class)
            ->where('type', FamilyFile::TYPE_IMAGE)
            ->where('is_primary', true);
    }

    public function thumbnail(): HasOne
    {
        return $this->hasOne(FamilyFile::class)
            ->where('type', FamilyFile::TYPE_THUMB)
            ->where('is_primary', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(FamilyFile::class)
            ->where('type', FamilyFile::TYPE_IMAGE);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(FamilyTag::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function tagList(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->tags->pluck('tag')->toArray()
        );
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_free) {
                    return 'Gratuito';
                }

                if (($this->price ?? 0) <= 0) {
                    return 'Exclusivo para assinantes';
                }

                return 'R$ ' . number_format($this->price, 2, ',', '.');
            }
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->primaryImage?->url
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->thumbnail?->url ?? $this->image_url
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
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }
    public function scopePremium($query)
    {
        return $query->where('is_free', false);
    }
    public function scopeParametric($query)
    {
        return $query->where('is_parametric', true);
    }

    public function scopeForRevitVersion($query, int $version)
    {
        return $query->where('revit_version', $version);
    }

    /**
     * Filters by category using the direct category_id column.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas(
                    'tags',
                    fn($t) =>
                    $t->where('tag', 'like', "%{$term}%")
                )
                ->orWhereHas(
                    'brand',
                    fn($b) =>
                    $b->where('name', 'like', "%{$term}%")
                );
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function incrementDownloads(): void
    {
        static::withoutTimestamps(fn() => $this->increment('download_count'));
    }

    public function syncTags(array $tags): void
    {
        $this->tags()->delete();

        // insert() bypasses model events, so FamilyTag's saving hook never
        // runs here: normalize (lowercase, no accents) before de-duplicating.
        $rows = collect($tags)
            ->map(fn($tag) => mb_strtolower(Str::ascii(trim($tag))))
            ->filter()
            ->unique()
            ->map(fn($tag) => [
                'family_id'  => $this->id,
                'tag'        => $tag,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()->toArray();

        if ($rows) {
            FamilyTag::insert($rows);
        }
    }

    // ─── Miscellaneous ─────────────────────────────────────────────
    public function isFree(): bool
    {
        return (bool) $this->is_free;
    }
}
