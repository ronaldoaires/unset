<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionService
{
    /**
     * Signs the user out everywhere except on the current device: deletes
     * their other stored sessions and rotates the remember-me token so old
     * "remember me" cookies stop working too.
     * Sessions are only deleted with the "database" driver, which is what
     * this project uses.
     */
    public function terminateOthers(User $user, Request $request): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }
}