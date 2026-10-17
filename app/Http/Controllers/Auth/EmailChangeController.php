<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Services\SessionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;

class EmailChangeController extends Controller
{
    /**
     * Opened from the link sent to the new address, with or without a session.
     */
    public function confirm(Request $request, int $id, string $hash, SessionService $sessions): Response
    {
        $user = $this->pendingUser($id, $hash);

        if (! $user) {
            return $this->result(false, 'Link inválido ou expirado', 'Este link não é mais válido. Faça um novo pedido de troca de e-mail na sua conta.');
        }

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        $taken = fn() => User::where('email', $newEmail)->whereKeyNot($user->id)->exists();

        try {
            // Someone may have registered this address since the request was made.
            if ($taken()) {
                $user->update(['pending_email' => null]);

                return $this->result(false, 'E-mail indisponível', 'Este e-mail já está em uso por outra conta. Escolha outro endereço.');
            }

            // email_verified_at is not mass assignable: the link proves ownership.
            $user->forceFill([
                'email'             => $newEmail,
                'pending_email'     => null,
                'email_verified_at' => now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            // A parallel signup took the address between the check and the save.
            $user->update(['pending_email' => null]);

            return $this->result(false, 'E-mail indisponível', 'Este e-mail já está em uso por outra conta. Escolha outro endereço.');
        }

        Notification::route('mail', $oldEmail)->notify(new EmailChangedNotification($newEmail));

        // Signs out every other device; the current browser keeps its session if it is this user's.
        $sessions->terminateOthers($user, $request);

        return $this->result(true, 'E-mail alterado!', 'Seu novo e-mail já está ativo. Use-o na próxima vez que entrar.');
    }

    /**
     * Opened from the notice sent to the current address.
     */
    public function cancel(int $id, string $hash): Response
    {
        $user = $this->pendingUser($id, $hash);

        if (! $user) {
            return $this->result(false, 'Pedido não encontrado', 'Este pedido já foi concluído, cancelado ou expirou.');
        }

        $user->update(['pending_email' => null]);

        return $this->result(true, 'Pedido cancelado', 'A troca de e-mail foi cancelada e nada mudou na sua conta. Se não foi você, troque sua senha.');
    }

    /**
     * The hash is tied to the pending address, so a link stops working as
     * soon as the request is cancelled, replaced or completed.
     */
    private function pendingUser(int $id, string $hash): ?User
    {
        $user = User::find($id);

        if (! $user || blank($user->pending_email)) {
            return null;
        }

        return hash_equals(sha1($user->pending_email), $hash) ? $user : null;
    }

    private function result(bool $ok, string $title, string $message): Response
    {
        return response()->view('auth.email-change-result', compact('ok', 'title', 'message'), $ok ? 200 : 403);
    }
}
