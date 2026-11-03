<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quote request or a "Fale conosco" message sent from the public site.
 */
class Lead extends Model
{
    protected $fillable = [
        'type',
        'status',
        'name',
        'email',
        'phone',
        'city',
        'state',
        'service_id',
        'project_id',
        'user_id',
        'message',
        'details',
        'notes',
        'source_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip',
        'user_agent',
        'privacy_accepted_at',
    ];

    protected $casts = [
        'type'                => LeadType::class,
        'status'              => LeadStatus::class,
        'details'             => 'array',
        'privacy_accepted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /**
     * wa.me link to answer the lead on WhatsApp (admin only). Assumes a
     * Brazilian number when the country code is missing.
     */
    public function whatsappUrl(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->phone);

        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return 'https://wa.me/' . $digits;
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', LeadStatus::open());
    }
}
