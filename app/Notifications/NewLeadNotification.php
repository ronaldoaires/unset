<?php

namespace App\Notifications;

use App\Enums\LeadType;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Phone;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the owner a new quote request or message arrived. "Reply" in the
 * mail client answers the client directly (reply-to).
 */
class NewLeadNotification extends Notification
{
    public function __construct(private readonly Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead;

        $subject = $lead->type === LeadType::Quote
            ? 'Novo orçamento' . ($lead->service ? ': ' . $lead->service->name : '') . ' - ' . $lead->name
            : 'Nova mensagem pelo site - ' . $lead->name;

        $mail = (new MailMessage)
            ->subject($subject)
            ->replyTo($lead->email, $lead->name)
            ->greeting($lead->type->getLabel() . ' recebido')
            ->line('**Nome:** ' . $lead->name)
            ->line('**E-mail:** ' . $lead->email);

        if ($lead->phone) {
            $mail->line('**WhatsApp:** ' . Phone::format($lead->phone));
        }

        if ($lead->city || $lead->state) {
            $mail->line('**Cidade:** ' . collect([$lead->city, $lead->state])->filter()->join('/'));
        }

        if ($lead->service) {
            $mail->line('**Serviço:** ' . $lead->service->name);
        }

        foreach ((array) $lead->details as $label => $value) {
            $mail->line('**' . $label . ':** ' . $value);
        }

        $mail->line('**Mensagem:**')
            ->line($lead->message)
            ->action('Abrir no painel', LeadResource::getUrl('index'));

        if ($lead->whatsappUrl()) {
            $mail->line('Responder no WhatsApp: ' . $lead->whatsappUrl());
        }

        return $mail;
    }
}
