<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * A token outlives the moment the account is deactivated in the admin panel,
     * so every authenticated call re-checks the flag and revokes the token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sua conta está desativada. Entre em contato com o suporte.',
                'code'    => 'account_inactive',
            ], 403);
        }

        return $next($request);
    }
}