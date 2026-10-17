<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Sent to the CURRENT address when a change is requested, with a one-click
 * way to cancel it in case the request was not made by the account owner.
 */
class EmailChangeRequestedNotification extends Notification
{
    public function __construct(
        private readonly int $userId,
        private readonly string $newEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cancelUrl = URL::temporarySignedRoute('email-change.cancel', now()->addMinutes(60), [
            'id'   => $this->userId,
            'hash' => sha1($this->newEmail),
        ]);

        return (new MailMessage)
            ->subject('Pedido de troca de e-mail na sua conta')
            ->greeting('Olá!')
            ->line('Alguém pediu para trocar o e-mail da sua conta em ' . config('app.name') . ' para ' . $this->maskedNewEmail() . '.')
            ->line('Se foi você, basta confirmar pelo link enviado ao novo endereço. Nada muda até lá.')
            ->line('Se NÃO foi você, cancele o pedido agora e troque sua senha.')
            ->action('Cancelar pedido', $cancelUrl);
    }

    private function maskedNewEmail(): string
    {
        [$local, $domain] = explode('@', $this->newEmail, 2);

        return Str::substr($local, 0, 2) . '***@' . $domain;
    }
}