<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DeviceAuthorizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Site side of the browser sign-in: the signed-in user confirms the code
 * shown by the plugin. Guests are sent to the login and come back here.
 */
class DeviceAuthorizationController extends Controller
{
    private const INVALID_CODE = 'Código inválido ou expirado. Confira o código no plugin ou comece o login novamente por lá.';

    public function show(Request $request, DeviceAuthorizationService $devices): View
    {
        $code = $request->query('codigo');

        if (blank($code)) {
            return view('auth.device.enter');
        }

        $authorization = $devices->findPending($code);

        if (! $authorization) {
            return view('auth.device.enter', ['error' => self::INVALID_CODE]);
        }

        return view('auth.device.confirm', ['authorization' => $authorization]);
    }

    public function update(Request $request, DeviceAuthorizationService $devices): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'action' => ['required', 'in:approve,deny'],
        ]);

        $authorization = $devices->findPending($data['codigo']);

        $answered = $authorization && ($data['action'] === 'approve'
            ? $devices->approve($authorization, $request->user())
            : $devices->deny($authorization, $request->user()));

        if (! $answered) {
            return redirect()->route('device.show')->withErrors(['codigo' => self::INVALID_CODE]);
        }

        return redirect()->route('device.done')->with('device_result', $data['action']);
    }

    /**
     * Result page after the redirect (a refresh does not repeat the answer).
     */
    public function done(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('device_result');

        if (! $result) {
            return redirect()->route('device.show');
        }

        return view('auth.device.done', ['approved' => $result === 'approve']);
    }
}
