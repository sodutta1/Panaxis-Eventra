<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Event $event, public ?string $attendeeName = null) {}

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
        $company = $this->event->company;

        return (new MailMessage)
            ->subject('Reminder: '.$this->event->title.' is coming up')
            ->greeting('Hello '.($this->attendeeName ?? 'there').',')
            ->line('This is a reminder that **'.$this->event->title.'** starts on '
                .optional($this->event->start_date)->format('d M Y, h:i A').'.')
            ->when($this->event->venue, fn ($m) => $m->line('Venue: '.$this->event->venue->name))
            ->line('Please carry your e-ticket for entry.')
            ->salutation('— '.($company->name ?? config('app.name')));
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        return [
            'company_id' => $this->event->company_id,
            'subject' => 'Event reminder: '.$this->event->title,
            'body' => 'Reminder: '.$this->event->title.' starts '
                .optional($this->event->start_date)->format('d M Y, h:i A')
                .($this->event->venue ? ' at '.$this->event->venue->name : '')
                .'. Carry your e-ticket for entry.',
        ];
    }
}
