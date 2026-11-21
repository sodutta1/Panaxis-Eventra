<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Notifications\EventReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendEventReminders extends Command
{
    protected $signature = 'eventra:send-reminders {--hours=24 : Look-ahead window in hours}';

    protected $description = 'Send pre-event reminders to ticket holders and guests of events starting soon.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $events = Event::with(['venue', 'tickets', 'guests'])
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereBetween('start_date', [now(), now()->addHours($hours)])
            ->get();

        $sent = 0;
        foreach ($events as $event) {
            $recipients = collect();
            foreach ($event->tickets as $ticket) {
                if ($ticket->payment_status === 'refunded' || $ticket->ticket_status === 'cancelled') {
                    continue;
                }
                $recipients->push([
                    'name' => $ticket->holder_name,
                    'mail' => $ticket->holder_email,
                    'whatsapp' => $ticket->holder_phone,
                ]);
            }
            foreach ($event->guests as $guest) {
                $recipients->push([
                    'name' => $guest->name,
                    'mail' => $guest->email,
                    'whatsapp' => $guest->phone,
                ]);
            }

            $recipients = $recipients
                ->filter(fn ($r) => $r['mail'] || $r['whatsapp'])
                ->unique(fn ($r) => ($r['mail'] ?: '').'|'.($r['whatsapp'] ?: ''));

            foreach ($recipients as $recipient) {
                $routes = [];
                if ($recipient['mail']) {
                    $routes['mail'] = $recipient['mail'];
                }
                if ($recipient['whatsapp']) {
                    $routes['whatsapp'] = $recipient['whatsapp'];
                }
                Notification::routes($routes)->notify(new EventReminderNotification($event, $recipient['name']));
                $sent++;
            }

            $event->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info("Reminders sent to {$sent} recipient(s) across {$events->count()} event(s).");

        return self::SUCCESS;
    }
}
