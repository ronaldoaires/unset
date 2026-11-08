<?php

namespace App\Policies;

use App\Models\Texture;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TexturePolicy
{
    // ─── Admin panel (Filament) ──────────────────────────────────────
    //
    // Same approval rules as FamilyPolicy: a moderator manages their own
    // submissions until a superadmin approves or edits them; from then on
    // only the superadmin may change or delete them.

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Texture $texture): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Texture $texture): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin()
            && $texture->user_id === $user->id
            && ($texture->approved_at === null || $texture->isRejected());
    }

    public function delete(User $user, Texture $texture): bool
    {
        return $this->update($user, $texture);
    }

    public function approve(User $user, Texture $texture): bool
    {
        return $user->isSuperAdmin();
    }

    public function reject(User $user, Texture $texture): bool
    {
        return $user->isSuperAdmin();
    }

    // ─── Plugin / storefront ─────────────────────────────────────────

    /**
     * Free textures: every signed-in user. Others: any active plan, with no
     * monthly quota and no one-off purchase. The code lets API clients
     * branch without parsing the message.
     */
    public function download(User $user, Texture $texture): Response
    {
        if ($texture->is_free) {
            return Response::allow();
        }

        if ($user->isPremium()) {
            return Response::allow();
        }

        return Response::deny('Esta textura é exclusiva para assinantes.', 'subscription_required');
    }
}
