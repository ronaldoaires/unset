<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Turns Mercado Pago data into local subscription state. Safe to run many
 * times for the same event (webhooks are retried and also arrive out of order).
 */
class SubscriptionSyncService
{
    public function __construct(private readonly MercadoPagoService $mp) {}

    /**
     * Applies the status of a Mercado Pago preapproval (the subscription itself).
     */
    public function syncPreapproval(array $mp): ?Subscription
    {
        $reference = $mp['external_reference'] ?? null;

        $subscription = Subscription::with('plan')->where('mp_preapproval_id', $mp['id'])->first()
            ?? (ctype_digit((string) $reference) ? Subscription::with('plan')->find((int) $reference) : null);

        if (! $subscription) {
            Log::warning('Mercado Pago: no local subscription for preapproval ' . $mp['id']);

            return null;
        }

        $nextPayment = isset($mp['next_payment_date'])
            ? Carbon::parse($mp['next_payment_date'])->setTimezone(config('app.timezone'))
            : null;

        $attributes = [
            'mp_preapproval_id' => $mp['id'],
            'mp_payer_id'       => isset($mp['payer_id']) ? (string) $mp['payer_id'] : $subscription->mp_payer_id,
            // Only the fields needed for debugging, never the whole payload.
            'mp_metadata'       => Arr::only($mp, ['id', 'status', 'reason', 'external_reference', 'next_payment_date', 'date_created', 'last_modified', 'payer_id']),
        ];

        // A canceled or expired subscription is never brought back by a late event.
        $final = in_array($subscription->status, ['canceled', 'expired'], true);
        $status = $mp['status'] ?? null;

        if (! $final && $status === 'authorized') {
            $onTrial = ($subscription->plan?->trial_days ?? 0) > 0 && $subscription->last_payment_at === null;

            $attributes += [
                'status'     => $onTrial ? 'trial' : 'active',
                'renews_at'  => $nextPayment ?? $subscription->renews_at,
                'expires_at' => null,
            ];

            if ($onTrial) {
                $attributes['trial_ends_at'] = $subscription->trial_ends_at
                    ?? now()->addDays($subscription->plan->trial_days);
            }
        } elseif (! $final && $status === 'paused') {
            $attributes['status'] = 'past_due';
        } elseif (! $final && $status === 'cancelled') {
            if ($subscription->status === 'pending') {
                // Never authorized: nothing was paid, so there is nothing to keep.
                $attributes += ['status' => 'expired', 'expires_at' => now()];
            } else {
                // Benefits last until the end of the period already paid. A subscription that
                // was past due has nothing paid to keep, so it ends right away.
                $attributes += [
                    'status'      => 'canceled',
                    'canceled_at' => now(),
                    'expires_at'  => $subscription->status === 'past_due'
                        ? now()
                        : ($subscription->renews_at ?? now()),
                ];
            }
        }

        $subscription->update($attributes);

        return $subscription;
    }

    /**
     * Records one invoice of a subscription (an "authorized payment").
     */
    public function syncAuthorizedPayment(array $authorized): void
    {
        $subscription = Subscription::where('mp_preapproval_id', $authorized['preapproval_id'] ?? '')->first();
        $paymentId    = data_get($authorized, 'payment.id');

        if (! $subscription || ! $paymentId) {
            // Scheduled invoices have no payment yet; the next event will carry it.
            return;
        }

        $payment = $this->mp->getPayment($paymentId);

        $status = match ($payment['status'] ?? null) {
            'approved'                => 'approved',
            'rejected'                => 'rejected',
            'refunded', 'charged_back' => 'refunded',
            'cancelled'               => 'cancelled',
            default                   => 'pending',
        };

        $paidAt = isset($payment['date_approved'])
            ? Carbon::parse($payment['date_approved'])->setTimezone(config('app.timezone'))
            : null;

        SubscriptionPayment::updateOrCreate(
            ['mp_payment_id' => (string) $paymentId],
            [
                'subscription_id'   => $subscription->id,
                'user_id'           => $subscription->user_id,
                'mp_preapproval_id' => $subscription->mp_preapproval_id,
                'status'            => $status,
                'amount'            => $payment['transaction_amount'] ?? $authorized['transaction_amount'] ?? 0,
                'currency'          => $payment['currency_id'] ?? 'BRL',
                'payment_method'    => $this->methodOf($payment),
                'payment_type'      => 'recurring',
                'paid_at'           => $paidAt,
                // The payment payload carries the payer's document and e-mail: keep only what we need.
                'mp_payload'        => Arr::only($payment, ['id', 'status', 'status_detail', 'payment_method_id', 'payment_type_id', 'transaction_amount', 'date_approved', 'external_reference']),
            ],
        );

        if ($status === 'approved') {
            $subscription->update([
                'status'              => 'active',
                'last_payment_at'     => $paidAt ?? now(),
                'last_payment_amount' => $payment['transaction_amount'] ?? null,
                'expires_at'          => null,
            ]);

            // Brings the next renewal date up to date.
            $this->syncPreapproval($this->mp->getPreapproval($subscription->mp_preapproval_id));
        } elseif ($status === 'rejected' && in_array($subscription->status, ['active', 'trial'], true)) {
            $subscription->update(['status' => 'past_due']);
        }
    }

    private function methodOf(array $payment): ?string
    {
        $type   = $payment['payment_type_id'] ?? null;
        $method = $payment['payment_method_id'] ?? null;

        return match (true) {
            $method === 'pix'                                    => 'pix',
            in_array($type, ['credit_card', 'debit_card'], true) => $type,
            default                                              => $type ?? $method,
        };
    }
}
