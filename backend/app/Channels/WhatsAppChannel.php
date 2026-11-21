<?php

namespace App\Channels;

use App\Models\NotificationLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notification channel, wired for a provider key but stubbed.
 *
 * When WHATSAPP_PROVIDER_KEY is set, this is where a real provider call
 * (Twilio/Meta Cloud API) goes. Until then every message is recorded in
 * notification_logs with status "stubbed_no_provider" so the flow is
 * auditable and nothing is silently dropped.
 */
class WhatsAppChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        $recipient = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $recipient) {
            return;
        }

        $hasProvider = (bool) config('services.whatsapp.provider_key');

        // Real provider integration point: with a key configured, call the
        // provider API here. Deliberately no real sends in this build.
        NotificationLog::create([
            'company_id' => $message['company_id'] ?? null,
            'channel' => 'whatsapp',
            'recipient' => $recipient,
            'notification' => class_basename($notification),
            'subject' => $message['subject'] ?? null,
            'body' => $message['body'],
            'status' => $hasProvider ? 'skipped_real_send' : 'stubbed_no_provider',
        ]);

        Log::info('[whatsapp-stub] to='.$recipient.' body='.$message['body']);
    }
}
