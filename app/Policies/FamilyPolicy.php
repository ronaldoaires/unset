<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FamilyPolicy
{
    // ─── Admin panel (Filament) ──────────────────────────────────────
    //
    // Rule: a moderator (role = admin) may create families freely and can
    // still edit/delete their OWN submissions, but only while nobody has
    // touched them yet (approved_at is null). The moment a superadmin
    // approves or edits a submission, it's considered "locked" — from
    // then on only the superadmin can modify or delete it.
    //
    // Superadmin bypasses all of the above and can do anything.

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Family $family): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Family $family): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin()
            && $family->user_id === $user->id
            && ($family->approved_at === null || $family->isRejected());
    }

    public function delete(User $user, Family $family): bool
    {
        return $this->update($user, $family);
    }

    /**
     * Custom ability for the "Approve" action in Filament.
     * Only the superadmin can approve or reject a submission — that's
     * exactly what locks it against further edits by the moderator.
     */
    public function approve(User $user, Family $family): bool
    {
        return $user->isSuperAdmin();
    }

    public function reject(User $user, Family $family): bool
    {
        return $user->isSuperAdmin();
    }

    // ─── Public storefront ───────────────────────────────────────────

    public function download(User $user, Family $family): Response
    {
        if ($family->is_free) {
            return Response::allow();
        }

        // A purchased family is always downloadable and never uses plan quota.
        if ($user->hasPurchasedFamily($family->id)) {
            return Response::allow();
        }

        if ($user->isPremium()) {
            return $user->canDownload($family)
                ? Response::allow()
                : Response::deny('Você atingiu o limite de downloads do seu plano neste mês.');
        }

        return Response::deny('Esta família é exclusiva para assinantes ou compra avulsa.');
    }
}
