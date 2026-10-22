<?php

namespace App\Services;

use App\Models\User;

/**
 * Builds the "what can this user do" block shared by the API responses
 * (login and /auth/me). The server decides; clients only display and cache it.
 */
class AccessService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        // Lazy loading is blocked outside production.
        $user->loadMissing('activeSubscription.plan');

        $active = $user->activeSubscription;
        $plan   = $active?->plan;

        // Same rule as the account page: when nothing grants access, still show the
        // latest subscription in any status so the client can explain why.
        $subscription = $active
            ?? $user->subscriptions()->with('plan')->latest('started_at')->latest('id')->first();

        $quota = $user->monthlyQuota();

        return [
            'has_access'   => $active !== null,
            'modules'      => $plan?->activeModules() ?? [],
            'permissions'  => $plan?->activePermissions() ?? [],
            'subscription' => $subscription ? [
                'status'        => $subscription->status,
                'status_label'  => $subscription->statusLabel(),
                'billing_cycle' => $subscription->billing_cycle,
                'plan'          => [
                    'slug' => $subscription->plan?->slug,
                    'name' => $subscription->plan?->name,
                ],
                'trial_ends_at' => $subscription->trial_ends_at?->toAtomString(),
                'renews_at'     => $subscription->renews_at?->toAtomString(),
                'expires_at'    => $subscription->expires_at?->toAtomString(),
            ] : null,
            'quota'        => $quota ? [
                'limit'     => $quota['limit'],
                'used'      => $quota['used'],
                'resets_at' => $quota['resets_at']->toAtomString(),
            ] : null,
        ];
    }
}