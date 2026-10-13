<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'mp_payment_id',
        'mp_preapproval_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payment_type',
        'paid_at',
        'mp_payload',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'paid_at'    => 'datetime',
        'mp_payload' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved'  => 'Aprovado',
            'rejected'  => 'Rejeitado',
            'pending'   => 'Pendente',
            'refunded'  => 'Estornado',
            'cancelled' => 'Cancelado',
            default     => ucfirst($this->status),
        };
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'credit_card' => 'Cartão de crédito',
            'debit_card'  => 'Cartão de débito',
            'pix'         => 'Pix',
            default       => $this->payment_method ?? '—',
        };
    }
}
