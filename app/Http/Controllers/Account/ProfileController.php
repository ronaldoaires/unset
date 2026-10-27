<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Rules\PersonName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('site.account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Validate the digits only; the mask characters are added back on save.
        $request->merge([
            'name'  => PersonName::normalize($request->input('name')),
            'phone' => preg_replace('/\D/', '', (string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'name'  => ['required', 'string', 'min:2', 'max:100', new PersonName()],
            'phone' => ['nullable', 'digits_between:10,11'],
        ], [
            'phone.digits_between' => 'Informe um telefone com DDD (10 ou 11 dígitos).',
        ]);

        // Email, role and is_active are never taken from this form.
        $request->user()->update([
            'name'  => $data['name'],
            'phone' => $this->formatPhone($data['phone'] ?? null),
        ]);

        return redirect()->route('account.profile')->with('status', 'Perfil atualizado.');
    }

    /**
     * "66999991234" -> "(66) 99999-1234"; 10 digits -> "(66) 9999-1234".
     */
    private function formatPhone(?string $digits): ?string
    {
        if (blank($digits)) {
            return null;
        }

        return strlen($digits) === 11
            ? sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7))
            : sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
    }
}