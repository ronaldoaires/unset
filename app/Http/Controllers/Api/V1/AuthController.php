<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\AccessService;
use App\Services\ApiTokenService;

class AuthController extends Controller
{
    public function login(Request $request, LoginLogService $logs, ApiTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        // Same key as the site login: failed attempts on either side count together.
        $throttleKey = Str::transliterate(Str::lower($data['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Muitas tentativas de acesso. Tente novamente em {$seconds} segundos.",
                'code'    => 'too_many_attempts',
            ], 429, ['Retry-After' => $seconds]);
        }

        $user = User::where('email', $data['email'])->first();

        // Google-only accounts have no password, so Hash::check fails for them too.
        // The message is the same for every failure, so it never reveals which e-mails exist.
        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            RateLimiter::hit($throttleKey);
            $logs->record($request, $user, false);

            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos. Se você criou a conta com o Google, defina uma senha em Minha conta > Senha, no site.',
            ]);
        }

        // From here on the password is correct, so these answers cannot be used
        // to discover registered e-mails.
        if (! $user->is_active) {
            RateLimiter::hit($throttleKey);

            return response()->json([
                'message' => 'Sua conta está desativada. Entre em contato com o suporte.',
                'code'    => 'account_inactive',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $logs->record($request, $user, false);

            return response()->json([
                'message' => 'Seu cadastro ainda não foi ativado. Clique no link que enviamos para o seu e-mail para poder entrar.',
                'code'    => 'email_not_verified',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        return response()->json($tokens->issue($user, $data['device_name'], $request));
    }

    public function me(Request $request, AccessService $access): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                ...(new UserResource($user))->resolve(),
                'access' => $access->summary($user),
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
