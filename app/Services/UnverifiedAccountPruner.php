<?php

namespace App\Services;

use App\Models\User;

/**
 * Deletes accounts that never confirmed their e-mail. Most of them are bot
 * sign-ups. Runs on each new registration, since the hosting has no scheduler.
 */
class UnverifiedAccountPruner
{
    public const AFTER_DAYS = 7;

    /** Keeps the registration request fast; the rest goes on the next one. */
    private const BATCH = 200;

    public function prune(): int
    {
        $ids = User::query()
            ->whereNull('email_verified_at')
            ->where('role', User::ROLE_USER)
            ->where('created_at', '<', now()->subDays(self::AFTER_DAYS))
            // Restrictive foreign keys: never touch accounts with real records.
            ->whereDoesntHave('subscriptions')
            ->whereDoesntHave('familyPurchases')
            ->whereDoesntHave('families')
            ->limit(self::BATCH)
            ->pluck('id');

        // Children (tokens, favorites, downloads, social accounts...) go by cascade.
        return $ids->isEmpty() ? 0 : User::whereKey($ids)->delete();
    }
}
