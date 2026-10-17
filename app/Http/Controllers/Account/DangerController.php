<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountDeletionRequestedNotification;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AccountClosingService;

class DangerController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('site.account.danger', [
            'user'         => $user,
            'hasPassword'  => filled($user->password),
            'blocker'      => $this->blocker($user),
            'scheduledFor' => $user->delete_requested_at?->copy()->addDays(User::DELETION_GRACE_DAYS),
        ]);
    }

    public function store(Request $request, SessionService $sessions): RedirectResponse
    {
        $user = $request->user();

        // A second request must not push the deadline further away.
        if ($user->delete_requested_at) {
            return redirect()->route('account.danger');
        }

        if ($message = $this->blocker($user)) {
            return redirect()->route('account.danger')->withErrors(['confirmation' => $message]);
        }

        $request->merge(['confirmation' => Str::upper(trim((string) $request->input('confirmation')))]);

        $rules = ['confirmation' => ['required', 'in:ENCERRAR']];

        // Accounts created through Google have no password to confirm.
        if (filled($user->password)) {
            $rules['password'] = ['required', 'current_password'];
        }

        $request->validate($rules, [
            'confirmation.required'     => 'Digite ENCERRAR para confirmar.',
            'confirmation.in'           => 'Digite ENCERRAR para confirmar.',
            'password.current_password' => 'A senha está incorreta.',
        ]);

        $user->forceFill(['delete_requested_at' => now()])->save();

        $scheduledFor = now()->addDays(User::DELETION_GRACE_DAYS);

        $user->notify(new AccountDeletionRequestedNotification($scheduledFor));

        // Signs out every other device; this browser keeps its session.
        $sessions->terminateOthers($user, $request);

        return redirect()->route('account.danger')->with(
            'status',
            'Pedido registrado. Sua conta será encerrada em ' . $scheduledFor->format('d/m/Y') . '.',
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['delete_requested_at' => null])->save();

        return redirect()->route('account.danger')->with('status', 'Pedido cancelado. Sua conta continua normal.');
    }

    /**
     * Reason why this account cannot be closed from here, or null when it can.
     */
    private function blocker(User $user): ?string
    {
        if (! $user->isUser()) {
            return 'Contas de moderador e superadmin são encerradas pelo suporte.';
        }

        $hasLiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trial', 'past_due'])
            ->exists();

        if (app(AccountClosingService::class)->hasLiveSubscription($user)) {
            return 'Você tem uma assinatura em andamento. Cancele a assinatura antes de encerrar a conta.';
        }

        return null;
    }
}