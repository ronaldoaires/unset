<?php

namespace App\Traits;

use App\Models\FamilyPurchase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasPremium
{
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * The subscription that currently grants access: active, a running trial,
     * or a canceled one that is still inside the period already paid for.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(\App\Models\Subscription::class)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(fn($q) => $q->where('status', 'trial')->where('trial_ends_at', '>', now()))
                    ->orWhere(fn($q) => $q->where('status', 'canceled')->where('expires_at', '>', now()));
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }

   /**
     * Ids of the families this user bought and paid for.
     */
    public function purchasedFamilyIds(): \Illuminate\Support\Collection
    {
        return $this->familyPurchases()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->pluck('family_id');
    }

    /**
     * Checks whether the user is a premium user.
     */
    public function isPremium(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Checks whether the user has purchased any family (status = paid).
     */
    public function familyPurchases(): HasMany
    {
        return $this->hasMany(FamilyPurchase::class);
    }

    /**
     * Checks whether the user has purchased a specific family (status = paid).
     */
    public function hasPurchasedFamily(int $familyId): bool
    {
        return $this->familyPurchases()
            ->where('family_id', $familyId)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->exists();
    }
}
