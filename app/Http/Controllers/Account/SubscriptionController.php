<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Notifications\SubscriptionCanceledNotification;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Throwable;

class SubscriptionController extends Controller
{
    private const PER_PAGE = 10;

    /** Reasons offered on the cancel form (stored in cancel_reason for churn analysis). */
    private const CANCEL_REASONS = [
        'price'           => 'O preço',
        'low_usage'       => 'Não estou usando o suficiente',
        'alternative'     => 'Encontrei uma alternativa',
        'missing_content' => 'Faltam famílias de que preciso',
        'other'           => 'Outro motivo',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $user->loadMissing('activeSubscription.plan');

        $hasAccess = $user->activeSubscription !== null;

        // The one granting access or, failing that, the latest in any status
        // (past due, canceled, expired), so the person sees why access ended.
        $subscription = $user->activeSubscription
            ?? $user->subscriptions()->with('plan')->latest('started_at')->latest('id')->first();

        $quota = $user->monthlyQuota();

        // The fragment brings the page back to the right section after a page change.
        $payments = SubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->with('subscription.plan')
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, pageName: 'pagamentos')
            ->withQueryString()
            ->fragment('pagamentos');

        $cancelReasons = self::CANCEL_REASONS;

        return view('site.account.subscription', compact('subscription', 'hasAccess', 'quota', 'payments', 'cancelReasons'));
    }

    public function cancel(Request $request, MercadoPagoService $mp, SubscriptionSyncService $sync): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'reason' => ['nullable', Rule::in(array_keys(self::CANCEL_REASONS))],
        ]);

        $subscription = $user->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trial', 'past_due'])
            ->latest('id')
            ->first();

        if (! $subscription || ! $subscription->canBeCanceled()) {
            return redirect()->route('account.subscription')
                ->with('status', 'Você não tem uma assinatura para cancelar.');
        }

        // Nothing changes locally unless Mercado Pago accepted the cancellation.
        try {
            $mp->cancelPreapproval($subscription->mp_preapproval_id);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('account.subscription')->withErrors([
                'cancel' => 'Não foi possível cancelar agora. Tente novamente em instantes.',
            ]);
        }

        // Same rule the webhook applies, so the later "cancelled" event changes nothing.
        $sync->syncPreapproval([
            'id'                 => $subscription->mp_preapproval_id,
            'status'             => 'cancelled',
            'external_reference' => (string) $subscription->id,
        ]);

        $subscription->refresh();
        $subscription->update(['cancel_reason' => $request->input('reason')]);

        $accessUntil = $subscription->expires_at?->isFuture() ? $subscription->expires_at : null;

        // The cancellation is already done: a mail failure must not turn it into an error page.
        try {
            $user->notify(new SubscriptionCanceledNotification($subscription->plan->name, $accessUntil));
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('account.subscription')->with(
            'status',
            $accessUntil
                ? 'Assinatura cancelada. Você mantém os benefícios até ' . $accessUntil->format('d/m/Y') . '.'
                : 'Assinatura cancelada.',
        );
    }
}
