<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, LoginLogService $logs): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas de acesso. Tente novamente em {$seconds} segundos.",
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        $passwordMatches = $user && Hash::check($credentials['password'], (string) $user->password);

        // The two checks below only run once the password is correct, so they
        // cannot be used to discover which e-mails are registered.
        if ($passwordMatches && ! $user->is_active) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Sua conta está desativada. Entre em contato com o suporte.',
            ]);
        }

        if ($passwordMatches && ! $user->hasVerifiedEmail()) {
            $logs->record($request, $user, false);

            // Lets the login view offer a "resend activation e-mail" button.
            $request->session()->flash('unverified_email', $user->email);

            throw ValidationException::withMessages([
                'email' => 'Seu cadastro ainda não foi ativado. Clique no link que enviamos para o seu e-mail para poder entrar.',
            ]);
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);
            $logs->record($request, $user, false);

            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $logs->record($request, Auth::user(), true);

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}