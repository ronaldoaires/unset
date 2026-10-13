<?php

namespace App\Policies;

use App\Models\SubscriptionPayment;
use App\Models\User;

class SubscriptionPaymentPolicy
{
    // Same rule as SubscriptionPolicy: this also governs the payments
    // page nested under SubscriptionResource.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, SubscriptionPayment $payment): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SubscriptionPayment $payment): bool
    {
        return false;
    }

    public function delete(User $user, SubscriptionPayment $payment): bool
    {
        return false;
    }
}