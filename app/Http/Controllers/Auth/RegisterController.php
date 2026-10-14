<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

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