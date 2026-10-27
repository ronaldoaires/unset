<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NotDisposableEmail;
use App\Rules\PersonName;
use App\Services\UnverifiedAccountPruner;
use App\Support\BotTrap;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, UnverifiedAccountPruner $pruner): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->input('email')));

        // Honeypot filled (bot): answer as if it worked, but create nothing
        // and send no e-mail. Too fast or Turnstile refused: validation error.
        if (! BotTrap::verify($request)) {
            return redirect()->route('register.done')->with('registered_email', $email);
        }

        $request->merge([
            'name'  => PersonName::normalize($request->input('name')),
            'email' => $email,
        ]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:100', new PersonName()],
            'email'    => [
                'required',
                'string',
                'max:255',
                // Strict RFC check plus a real mail server (MX/A record) for the domain.
                Rule::email()->rfcCompliant(strict: true)->validateMxRecord()->preventSpoofing(),
                // Only the characters real providers accept before the "@".
                'regex:/^[a-z0-9._%+\-]+@/',
                // Temporary inbox providers (public list + config/disposable-email.php "blocked").
                new NotDisposableEmail(),
                'unique:users,email',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.regex'                    => 'Use um e-mail válido, sem caracteres especiais antes do @.',
        ]);

        // No scheduler on the hosting: abandoned sign-ups are cleaned up here.
        $pruner->prune();

        // role and is_active are fillable on User, so only the three validated
        // fields are passed here and the role is set explicitly, never from input.
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // hashed by the model cast
            'role'     => User::ROLE_USER,
        ]);

        // Sends the verification e-mail. The user is NOT logged in here, so
        // last_login_at only ever reflects real logins.
        event(new Registered($user));

        return redirect()->route('register.done')->with('registered_email', $user->email);
    }

    public function done(): View|RedirectResponse
    {
        $email = session('registered_email');

        // Flash data lives for one request: a refresh sends the user to the login.
        return $email
            ? view('auth.registered', ['email' => $email])
            : redirect()->route('login');
    }
}