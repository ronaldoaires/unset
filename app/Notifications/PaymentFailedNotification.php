<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once when a subscription becomes "past due" because a charge was
 * rejected (or Mercado Pago paused the subscription after failed retries).
 */
class PaymentFailedNotification extends Notification
{
    public function __construct(private readonly string $planName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Não conseguimos confirmar o seu pagamento')
            ->greeting('Olá!')
            ->line('A cobrança da sua assinatura do plano ' . $this->planName . ' em ' . config('app.name') . ' não foi aprovada.')
            ->line('Confira se o cartão está válido e tem limite disponível. Enquanto o pagamento não for regularizado, os benefícios do plano ficam suspensos.')
            ->line('Se o problema continuar, você pode cancelar a assinatura e assinar de novo com outro cartão.')
            ->action('Ver minha assinatura', route('account.subscription'));
    }
}