<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    // Leads hold clients' personal data: superadmin only. They are created
    // by the public forms, never by hand in the panel.

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->isSuperAdmin();
    }

    // Kept so a client's data can be erased on request (LGPD).
    public function delete(User $user, Lead $lead): bool
    {
        return $user->isSuperAdmin();
    }
}
