<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the OLD address after the change went through.
 */
class EmailChangedNotification extends Notification
{
    public function __construct(private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$local, $domain] = explode('@', $this->newEmail, 2);

        return (new MailMessage)
            ->subject('O e-mail da sua conta foi alterado')
            ->greeting('Olá!')
            ->line('O e-mail da sua conta em ' . config('app.name') . ' agora é ' . Str::substr($local, 0, 2) . '***@' . $domain . '.')
            ->line('Se não foi você, entre em contato com o suporte imediatamente.');
    }
}