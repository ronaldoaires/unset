<?php

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;

class SubcategoryPolicy
{
    // Same rule as CategoryPolicy — this also governs the
    // SubcategoriesRelationManager nested under CategoryResource.

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Subcategory $subcategory): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Subcategory $subcategory): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Subcategory $subcategory): bool
    {
        return $user->isSuperAdmin();
    }
}