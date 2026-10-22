<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSecurity;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // The result is ignored on purpose: the same message is shown whether or
        // not the address exists, so the form cannot reveal registered e-mails.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Se este e-mail estiver cadastrado, enviamos um link para redefinir a senha.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => $password, // hashed by the model cast
                    'remember_token' => Str::random(60),
                ])->save();
                
                // A reset means the old credentials may be compromised: revoke API tokens.
                $user->tokens()->delete();

                // Opening the e-mailed link proves the user owns the address.
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                // Feeds the "last password change" shown in the admin panel.
                UserSecurity::updateOrCreate(
                    ['user_id' => $user->id],
                    ['password_changed_at' => now()],
                );

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}