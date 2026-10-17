<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'family_id',
        'amount',
        'status',
        'payment_id',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'paid'      => 'Pago',
            'pending'   => 'Pendente',
            'refunded'  => 'Estornado',
            'failed'    => 'Falhou',
            'cancelled' => 'Cancelado',
            default     => ucfirst((string) $this->status),
        };
    }

    /**
     * Tone of the status badge in the account area (see x-ui.badge).
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'paid'     => 'success',
            'pending'  => 'warning',
            'refunded' => 'info',
            'failed'   => 'danger',
            default    => 'neutral',
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
