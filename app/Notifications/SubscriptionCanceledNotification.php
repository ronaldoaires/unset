<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a cancellation requested by the customer.
 */
class SubscriptionCanceledNotification extends Notification
{
    public function __construct(
        private readonly string $planName,
        private readonly ?CarbonInterface $accessUntil,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Sua assinatura foi cancelada')
            ->greeting('Olá!')
            ->line('Cancelamos a sua assinatura do plano ' . $this->planName . ' em ' . config('app.name') . '. Não haverá novas cobranças.');

        $message->line($this->accessUntil
            ? 'Você continua com os benefícios do plano até ' . $this->accessUntil->format('d/m/Y') . '.'
            : 'Os benefícios do plano foram encerrados.');

        return $message
            ->action('Ver minha assinatura', route('account.subscription'))
            ->line('Se não foi você, troque a sua senha e fale com o nosso suporte.');
    }
}