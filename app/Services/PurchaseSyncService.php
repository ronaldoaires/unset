<?php

namespace App\Services;

use App\Models\FamilyPurchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Turns a Mercado Pago payment into local purchase state. Safe to run many
 * times for the same event (webhooks are retried and arrive out of order).
 */
class PurchaseSyncService
{
    private const REFERENCE_PREFIX = 'purchase-';

    /**
     * Applies one Mercado Pago payment to the purchase it belongs to.
     * Returns null when the payment is not a family purchase.
     */
    public function syncPayment(array $payment): ?FamilyPurchase
    {
        $reference = (string) ($payment['external_reference'] ?? '');

        // Subscription charges also trigger "payment" notifications: only
        // references created for purchases are handled here.
        if (! str_starts_with($reference, self::REFERENCE_PREFIX)) {
            return null;
        }

        $id = substr($reference, strlen(self::REFERENCE_PREFIX));

        if (! ctype_digit($id)) {
            return null;
        }

        $purchase = FamilyPurchase::find((int) $id);

        if (! $purchase) {
            Log::warning('Mercado Pago: no local purchase for reference ' . $reference);

            return null;
        }

        $paymentId = (string) $payment['id'];

        // A paid purchase is never touched by another payment (an old rejected
        // attempt arriving late, or a duplicated payment).
        if ($purchase->status === 'paid' && $purchase->payment_id !== $paymentId) {
            Log::warning("Mercado Pago: payment {$paymentId} ignored, purchase {$purchase->id} is already paid.");

            return $purchase;
        }

        $status = match ($payment['status'] ?? null) {
            'approved'                  => 'paid',
            'rejected'                  => 'failed',
            'cancelled'                 => 'cancelled',
            'refunded', 'charged_back'  => 'refunded',
            default                     => 'pending', // pending, in_process, authorized...
        };

        $attributes = [
            'status'         => $status,
            'payment_id'     => $paymentId,
            'payment_method' => $this->methodOf($payment),
        ];

        if ($status === 'paid') {
            $attributes['paid_at'] = isset($payment['date_approved'])
                ? Carbon::parse($payment['date_approved'])->setTimezone(config('app.timezone'))
                : now();

            // The amount really charged is the financial truth.
            $attributes['amount'] = $payment['transaction_amount'] ?? $purchase->amount;
        }

        $purchase->update($attributes);

        return $purchase;
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