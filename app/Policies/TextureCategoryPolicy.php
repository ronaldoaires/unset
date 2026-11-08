<?php

namespace App\Policies;

use App\Models\TextureCategory;
use App\Models\User;

class TextureCategoryPolicy
{
    // Catalog structure: any admin can browse (needed to pick a category),
    // only the superadmin manages it. Same rule as family categories.

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TextureCategory $category): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, TextureCategory $category): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, TextureCategory $category): bool
    {
        return $user->isSuperAdmin();
    }
}
