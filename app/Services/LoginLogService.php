<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginLogService
{
    /**
     * Writes one row to the access history shown in the admin panel and,
     * on success, stamps the user's last login date.
     */
    public function record(Request $request, ?User $user, bool $success): void
    {
        LoginLog::create([
            'user_id'    => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success'    => $success,
        ]);

        if ($success && $user) {
            $user->forceFill(['last_login_at' => now()])->save();
        }
    }
}