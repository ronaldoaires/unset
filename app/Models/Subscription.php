<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'previous_plan_id',
        'billing_cycle',
        'status',
        'mp_preapproval_id',
        'mp_payer_id',
        'mp_external_reference',
        'started_at',
        'trial_ends_at',
        'renews_at',
        'expires_at',
        'canceled_at',
        'last_payment_at',
        'last_payment_amount',
        'cancel_reason',
        'mp_metadata',
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'trial_ends_at'       => 'datetime',
        'renews_at'           => 'datetime',
        'expires_at'          => 'datetime',
        'canceled_at'         => 'datetime',
        'last_payment_at'     => 'datetime',
        'last_payment_amount' => 'decimal:2',
        'mp_metadata'         => 'array',
    ];

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function previousPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'previous_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    // ── Status helpers ───────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * A subscription is valid when it's active OR still in a valid trial.
     */
    public function isValid(): bool
    {
        return $this->isActive() || $this->isOnTrial();
    }

    public function isYearly(): bool
    {
        return $this->billing_cycle === 'yearly';
    }

    public function isMonthly(): bool
    {
        return $this->billing_cycle === 'monthly';
    }

    /**
     * Days remaining until expiration/renewal.
     */
    public function daysUntilRenewal(): ?int
    {
        if (! $this->renews_at) {
            return null;
        }

        return (int) now()->diffInDays($this->renews_at, absolute: false);
    }

    /**
     * Status label for display in the UI.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'active'   => 'Ativa',
            'trial'    => 'Trial',
            'canceled' => 'Cancelada',
            'past_due' => 'Pagamento pendente',
            'expired'  => 'Expirada',
            'pending'  => 'Aguardando pagamento',
            default    => ucfirst($this->status),
        };
    }

    /**
     * Status badge color for the UI (Tailwind classes).
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'active'   => 'green',
            'trial'    => 'blue',
            'canceled' => 'red',
            'past_due' => 'amber',
            'expired'  => 'zinc',
            default    => 'zinc',
        };
    }

    /**
     * Tone of the status badge in the account area (see x-ui.badge).
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'active'   => 'success',
            'trial'    => 'info',
            'canceled' => 'danger',
            'past_due' => 'warning',
            'pending'  => 'warning',
            default    => 'neutral',
        };
    }

    /**
     * One-line explanation of the current situation, shown under the plan name.
     */
    public function statusMessage(): ?string
    {
        return match ($this->status) {
            'active'   => $this->renews_at
                ? 'Próxima renovação em ' . $this->renews_at->format('d/m/Y') . '.'
                : null,
            'trial'    => $this->trial_ends_at
                ? 'Período de teste grátis até ' . $this->trial_ends_at->format('d/m/Y') . '.'
                : null,
            'canceled' => $this->expires_at?->isFuture()
                ? 'Assinatura cancelada. Você mantém os benefícios até ' . $this->expires_at->format('d/m/Y') . '.'
                : 'Assinatura cancelada. Os benefícios foram encerrados.',
            'past_due' => 'Não conseguimos confirmar o último pagamento. Regularize para voltar a usar os benefícios do plano.',
            'expired'  => $this->expires_at
                ? 'Assinatura expirada em ' . $this->expires_at->format('d/m/Y') . '.'
                : 'Assinatura expirada.',
            'pending'  => 'Estamos aguardando a confirmação do pagamento.',
            default    => null,
        };
    }

    /**
     * Whether the customer can cancel it from the account page: it must still
     * be running and have a Mercado Pago subscription to cancel.
     */
    public function canBeCanceled(): bool
    {
        return in_array($this->status, ['active', 'trial', 'past_due'], true)
            && filled($this->mp_preapproval_id);
    }
}
