<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Notifications\EmailChangeRequestedNotification;
use App\Notifications\VerifyNewEmailNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmailController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('site.account.email', [
            'user'        => $user,
            'hasPassword' => filled($user->password),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user        = $request->user();
        $hasPassword = filled($user->password);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $rules = [
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::notIn([$user->email]),
                Rule::unique('users', 'email'),
            ],
        ];

        // Accounts created through Google have no password to confirm.
        if ($hasPassword) {
            $rules['password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules, [
            'email.not_in'              => 'Este já é o e-mail da sua conta.',
            'email.unique'              => 'Este e-mail já está em uso por outra conta.',
            'password.current_password' => 'A senha está incorreta.',
        ]);

        // The current e-mail stays valid until the new one is confirmed.
        $user->update(['pending_email' => $data['email']]);

        Notification::route('mail', $data['email'])
            ->notify(new VerifyNewEmailNotification($user->id, $data['email']));

        // Goes to the current address (Notifiable on the User model).
        $user->notify(new EmailChangeRequestedNotification($user->id, $data['email']));

        return redirect()->route('account.email')->with(
            'status',
            'Enviamos um link de confirmação para ' . $data['email'] . '. O e-mail só muda depois que você clicar nele.',
        );
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->user()->update(['pending_email' => null]);

        return redirect()->route('account.email')->with('status', 'Pedido de troca de e-mail cancelado.');
    }
}