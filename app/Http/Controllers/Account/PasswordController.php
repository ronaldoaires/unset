<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserSecurity;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('site.account.password', [
            // Accounts created through Google have no password until they set one here.
            'hasPassword' => filled($request->user()->password),
        ]);
    }

    public function update(Request $request, SessionService $sessions): RedirectResponse
    {
        $user        = $request->user();
        $hasPassword = filled($user->password);

        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // The current password is only asked (and checked) when one exists.
        if ($hasPassword) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'][]       = 'different:current_password';
        }

        $data = $request->validate($rules, [
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.different'                => 'A nova senha deve ser diferente da atual.',
        ]);

        // Hashed by the "hashed" cast on the User model.
        $user->update(['password' => $data['password']]);

        // Feeds the "last password change" shown in the admin panel.
        UserSecurity::updateOrCreate(
            ['user_id' => $user->id],
            ['password_changed_at' => now()],
        );

        $sessions->terminateOthers($user, $request);

        return redirect()->route('account.password')->with(
            'status',
            $hasPassword ? 'Senha alterada. Os outros dispositivos foram desconectados.' : 'Senha criada. Agora você também pode entrar com e-mail e senha.',
        );
    }
}