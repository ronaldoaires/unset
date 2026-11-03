<?php

namespace App\Models;

use App\Services\ImageService;
use App\Traits\HasPublicImages;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    use HasPublicImages;

    protected $fillable = [
        'project_id',
        'path',
        'alt',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // The file goes away together with the record.
        static::deleting(function (ProjectImage $image) {
            app(ImageService::class)->deleteImageAndThumb($image->path, 'public');
        });
    }

    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => $this->publicImageUrl($this->path));
    }

    protected function thumbUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->publicThumbUrl($this->path));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
