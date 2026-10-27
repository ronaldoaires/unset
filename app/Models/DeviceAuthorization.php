<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One browser sign-in request of a desktop client (see DeviceAuthorizationService).
 */
class DeviceAuthorization extends Model
{
    protected $fillable = [
        'device_code_hash',
        'user_code',
        'device_name',
        'ip_address',
        'expires_at',
    ];

    protected $casts = [
        'approved_at'    => 'datetime',
        'denied_at'      => 'datetime',
        'consumed_at'    => 'datetime',
        'last_polled_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Still waiting for the user's answer and not expired.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_at')
            ->whereNull('denied_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * "KXQPWMZB" → "KXQP-WMZB", the way users read and type it.
     */
    public function formattedUserCode(): string
    {
        return substr($this->user_code, 0, 4) . '-' . substr($this->user_code, 4);
    }
}
