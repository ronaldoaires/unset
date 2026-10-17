<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    private const PER_PAGE = 10;

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

        $purchases = $user->familyPurchases()
            ->with('family')
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, pageName: 'compras')
            ->withQueryString()
            ->fragment('compras');

        return view('site.account.subscription', compact('subscription', 'hasAccess', 'quota', 'payments', 'purchases'));
    }
}
