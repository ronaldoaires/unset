<?php

namespace App\Notifications;

use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Auto-reply to the client: confirms we got the request and when to expect
 * an answer. Deliberately short and without links, so it is never useful
 * to someone abusing the form to send mail to other people.
 */
class LeadReceivedNotification extends Notification
{
    public function __construct(private readonly Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company   = config('showcase.company.name');
        $replyTime = config('showcase.contact.reply_time');
        $firstName = strtok($this->lead->name, ' ');

        $what = $this->lead->type === LeadType::Quote
            ? 'seu pedido de orçamento' . ($this->lead->service ? ' de ' . mb_strtolower($this->lead->service->name) : '')
            : 'sua mensagem';

        return (new MailMessage)
            ->subject('Recebemos ' . $what)
            ->greeting('Olá, ' . $firstName . '!')
            ->line('Recebemos ' . $what . ' e vamos responder ' . $replyTime . '.')
            ->line('Se quiser acrescentar alguma informação, é só responder este e-mail.')
            ->salutation('Atenciosamente, ' . $company . ' - ' . mb_strtolower(config('showcase.responsible.role')) . ' ' . config('showcase.responsible.name'));
    }
}
