<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    // Subscriptions are created and changed by the checkout flow and the
    // payment gateway webhook. The panel is read-only and superadmin-only.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return false;
    }
}