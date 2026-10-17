<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent to the NEW address: proves the person owns it.
 */
class VerifyNewEmailNotification extends Notification
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
        // The hash ties the link to this exact pending address.
        $url = URL::temporarySignedRoute('email-change.confirm', now()->addMinutes(60), [
            'id'   => $this->userId,
            'hash' => sha1($this->newEmail),
        ]);

        return (new MailMessage)
            ->subject('Confirme seu novo e-mail')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para usar este endereço como e-mail da sua conta em ' . config('app.name') . '.')
            ->action('Confirmar novo e-mail', $url)
            ->line('Este link expira em 60 minutos. Se você não pediu essa alteração, ignore esta mensagem.');
    }
}