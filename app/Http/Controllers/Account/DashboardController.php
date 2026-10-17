<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const RECENT_DOWNLOADS = 3;

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Lazy loading is blocked outside production, so load what the view reads.
        $user->loadMissing('activeSubscription.plan');

        $hasAccess = $user->activeSubscription !== null;

        // The one granting access or, failing that, the latest in any status,
        // so the person sees why access ended (past due, canceled, expired).
        $subscription = $user->activeSubscription
            ?? $user->subscriptions()->with('plan')->latest('started_at')->latest('id')->first();

        $ownDownloads = fn ($query) => $query->where('user_id', $user->id);

        // Same rule as the downloads page: one row per published family.
        $downloadedFamilies = Family::published()->whereHas('downloads', $ownDownloads);

        $recentDownloads = (clone $downloadedFamilies)
            ->withMax(['downloads as last_downloaded_at' => $ownDownloads], 'downloaded_at')
            ->withCasts(['last_downloaded_at' => 'datetime'])
            ->with(['category', 'brand', 'thumbnail', 'primaryImage'])
            ->orderByDesc('last_downloaded_at')
            ->orderByDesc('id')
            ->take(self::RECENT_DOWNLOADS)
            ->get();

        return view('site.account.index', [
            'user'            => $user,
            'subscription'    => $subscription,
            'hasAccess'       => $hasAccess,
            'quota'           => $user->monthlyQuota(),
            'downloadsCount'  => $downloadedFamilies->count(),
            'favoritesCount'  => $user->favorites()->whereHas('family', fn ($query) => $query->published())->count(),
            'recentDownloads' => $recentDownloads,
            'hasPassword'     => filled($user->password),
            'deletionDate'    => $user->delete_requested_at?->copy()->addDays(User::DELETION_GRACE_DAYS),
        ]);
    }
}