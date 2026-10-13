<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    // Categories are catalog structure, not moderator-owned content — any
    // admin can browse them (they need to when picking a category for a
    // family), but only the superadmin can create/edit/delete them.

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isSuperAdmin();
    }
}