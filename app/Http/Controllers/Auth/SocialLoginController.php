<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)
            // Always let the person pick the Google account.
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request, string $provider, LoginLogService $logs): RedirectResponse
    {
        // Sent back by the provider when the person cancels on the consent screen.
        if ($request->has('error')) {
            return $this->fail('Você cancelou o login com o Google.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Não foi possível entrar com o Google. Tente novamente.');
        }

        $raw   = $socialUser->user ?? [];
        $email = Str::lower(trim((string) $socialUser->getEmail()));

        // Never trust an address the provider did not confirm: it is what we
        // use to match (and link) existing accounts.
        $emailVerified = filter_var(
            $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($email === '' || ! $emailVerified) {
            return $this->fail('O Google não informou um e-mail verificado para esta conta.');
        }

        $user = DB::transaction(fn () => $this->resolveUser(
            $provider,
            (string) $socialUser->getId(),
            $email,
            trim((string) $socialUser->getName()),
        ));

        if (! $user->is_active) {
            return $this->fail('Sua conta está desativada. Entre em contato com o suporte.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $logs->record($request, $user, true);

        return redirect()->intended(route('home'));
    }

    /**
     * 1) provider account already linked -> that user;
     * 2) e-mail already registered       -> link the provider to that user;
     * 3) otherwise                       -> create a new verified user.
     */
    private function resolveUser(string $provider, string $providerId, string $email, string $name): User
    {
        $account = SocialAccount::with('user')
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($account) {
            return $account->user;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $user->hasVerifiedEmail()) {
                // The address was never confirmed on this account, so whoever
                // created it may not own it. Their password is dropped before
                // the real owner takes the account over.
                $user->forceFill([
                    'password'       => null,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->markEmailAsVerified();
            }
        } else {
            $user = User::create([
                'name'     => $name !== '' ? $name : Str::before($email, '@'),
                'email'    => $email,
                'password' => null,
                'role'     => User::ROLE_USER,
            ]);

            // The provider already confirmed the address.
            $user->markEmailAsVerified();
        }

        $user->socialAccounts()->create([
            'provider'    => $provider,
            'provider_id' => $providerId,
        ]);

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['social' => $message]);
    }
}