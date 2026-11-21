<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\EventTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public EventTask $task) {}

    public function via(mixed $notifiable): array
    {
        $channels = ['mail'];
        if ($notifiable->routeNotificationFor('whatsapp', $this)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task due: '.$this->task->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your task **'.$this->task->title.'** for event "'.$this->task->event->title
                .'" is due on '.optional($this->task->due_date)->format('d M Y').'.')
            ->line('Priority: '.ucfirst($this->task->priority).' · Phase: '.str_replace('_', ' ', ucfirst($this->task->phase)))
            ->line('Please update the task status once done.');
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        return [
            'company_id' => $this->task->event->company_id,
            'subject' => 'Task due: '.$this->task->title,
            'body' => 'Task "'.$this->task->title.'" for event '.$this->task->event->title
                .' is due on '.optional($this->task->due_date)->format('d M Y').'.',
        ];
    }
}
