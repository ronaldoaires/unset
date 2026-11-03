<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    // The services showcase is the owner's sales channel: superadmin only.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Project $record): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Project $record): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Project $record): bool
    {
        return $user->isSuperAdmin();
    }
}
