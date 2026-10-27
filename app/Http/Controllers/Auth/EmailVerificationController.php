<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BotTrap;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    /**
     * Fallback screen for a logged-in user whose e-mail is not confirmed
     * (the "verified" middleware sends them here).
     */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('home')
            : view('auth.verify-email');
    }

    /**
     * Opened from the e-mailed link, with or without a session.
     */
    public function verify(int $id, string $hash): View
    {
        $user = User::findOrFail($id);

        // The hash is tied to the user's current e-mail address.
        abort_unless(hash_equals(sha1((string) $user->getEmailForVerification()), $hash), 403);

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return view('auth.email-verified', ['alreadyVerified' => $alreadyVerified]);
    }

    /**
     * Public resend, used by people who are blocked at login or whose link expired.
     */
    public function resend(Request $request): RedirectResponse
    {
        // Honeypot filled (bot): same answer below, but no e-mail is sent.
        $human = BotTrap::verify($request);

        $email = Str::lower(trim((string) $request->validate([
            'email' => ['required', 'email'],
        ])['email']));

        $user = User::where('email', $email)->first();

        if ($human && $user && ! $user->hasVerifiedEmail()) {
            // One e-mail per minute per address, on top of the per-IP route throttle.
            RateLimiter::attempt(
                'verification-resend:' . $email,
                1,
                fn () => $user->sendEmailVerificationNotification(),
                60,
            );
        }

        // Same answer whether or not the address exists or is already verified,
        // so this form cannot be used to discover registered e-mails.
        return redirect()->route('register.done')
            ->with('registered_email', $email)
            ->with('status', 'Se existir um cadastro pendente com este e-mail, enviamos um novo link de ativação.');
    }

    /**
     * Resend for a logged-in user (fallback screen).
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Enviamos um novo link de verificação para o seu e-mail.');
    }
}