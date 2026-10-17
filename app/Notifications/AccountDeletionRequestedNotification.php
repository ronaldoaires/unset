<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms the deletion request by e-mail, with a link to cancel it.
 */
class AccountDeletionRequestedNotification extends Notification
{
    public function __construct(private readonly CarbonInterface $scheduledFor) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pedido de encerramento da sua conta')
            ->greeting('Olá!')
            ->line('Recebemos o pedido para encerrar a sua conta em ' . config('app.name') . '.')
            ->line('Ela será encerrada em ' . $this->scheduledFor->format('d/m/Y') . '. Até lá, você pode cancelar o pedido quando quiser.')
            ->action('Cancelar pedido', route('account.danger'))
            ->line('Se não foi você, entre na conta, cancele o pedido e troque sua senha.');
    }
}