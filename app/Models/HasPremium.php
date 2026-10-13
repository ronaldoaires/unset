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

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(\App\Models\Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }

    public function isPremium(): bool
    {
        return $this->activeSubscription()->exists();
    }

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
