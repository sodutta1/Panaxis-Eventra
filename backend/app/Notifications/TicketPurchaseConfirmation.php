<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketPurchaseConfirmation extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(mixed $notifiable): array
    {
        $channels = [];
        if ($notifiable->routeNotificationFor('mail', $this)) {
            $channels[] = 'mail';
        }
        if ($notifiable->routeNotificationFor('whatsapp', $this)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $event = $this->ticket->event;
        $company = $event->company;

        return (new MailMessage)
            ->subject('Your ticket for '.$event->title)
            ->greeting('Hello '.$this->ticket->holder_name.',')
            ->line('Your ticket for **'.$event->title.'** is confirmed.')
            ->line('Ticket: '.$this->ticket->ticket_number.' ('.$this->ticket->ticketType->name.')')
            ->line('Event date: '.optional($event->start_date)->format('d M Y, h:i A'))
            ->when($event->venue, fn ($m) => $m->line('Venue: '.$event->venue->name))
            ->line('Show the QR code on your e-ticket at the gate.')
            ->salutation('— '.($company->name ?? config('app.name')));
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        $event = $this->ticket->event;

        return [
            'company_id' => $this->ticket->company_id,
            'subject' => 'Ticket confirmation '.$this->ticket->ticket_number,
            'body' => 'Hi '.$this->ticket->holder_name.', your ticket '.$this->ticket->ticket_number
                .' for '.$event->title.' on '.optional($event->start_date)->format('d M Y')
                .' is confirmed. Show your e-ticket QR at the gate.',
        ];
    }
}
