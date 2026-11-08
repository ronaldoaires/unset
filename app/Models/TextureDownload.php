<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextureDownload extends Model
{
    protected $fillable = [
        'user_id',
        'texture_id',
        'resolution',
        'version',
        'ip_address',
        'downloaded_at',
    ];

    protected $casts = [
        'version'       => 'integer',
        'downloaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function texture(): BelongsTo
    {
        return $this->belongsTo(Texture::class);
    }
}
