<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    // The services showcase is the owner's sales channel: superadmin only.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Service $record): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Service $record): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Service $record): bool
    {
        return $user->isSuperAdmin();
    }
}
