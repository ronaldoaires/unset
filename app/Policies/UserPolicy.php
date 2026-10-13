<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // User management is superadmin-only. Moderators (role = admin)
    // never see this resource.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // A superadmin can never delete their own account from the panel,
        // otherwise the system could be left without a superadmin.
        return $user->isSuperAdmin() && ! $user->is($model);
    }
}