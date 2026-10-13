<?php

namespace App\Policies;

use App\Models\FamilyPurchase;
use App\Models\User;

class FamilyPurchasePolicy
{
    // Purchases are created by the checkout flow and the payment webhook.
    // The panel is read-only and superadmin-only.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, FamilyPurchase $purchase): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, FamilyPurchase $purchase): bool
    {
        return false;
    }

    public function delete(User $user, FamilyPurchase $purchase): bool
    {
        return false;
    }
}