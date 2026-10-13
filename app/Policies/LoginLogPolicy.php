<?php

namespace App\Policies;

use App\Models\LoginLog;
use App\Models\User;

class LoginLogPolicy
{
    // Access history is an audit trail: superadmin can read it,
    // nobody can create, edit or delete entries from the panel.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, LoginLog $log): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LoginLog $log): bool
    {
        return false;
    }

    public function delete(User $user, LoginLog $log): bool
    {
        return false;
    }
}