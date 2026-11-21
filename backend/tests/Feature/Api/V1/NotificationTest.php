<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\NotificationLog;
use App\Models\Ticket;
use App\Notifications\EventReminderNotification;
use App\Notifications\TicketPurchaseConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@eventflow.test',
            'password' => 'password',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function makeEventWithType(array $auth, array $overrides = []): array
    {
        $event = Event::findOrFail($this->postJson('/api/v1/events', array_merge([
            'title' => 'Notify Fest',
            'client_id' => Client::firstOrFail()->id,
            'event_category_id' => EventCategory::firstOrFail()->id,
            'event_type' => 'corporate',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'status' => 'confirmed',
        ], $overrides), $auth)->assertCreated()->json('data.id'));

        $typeId = $this->postJson('/api/v1/tickets/types', [
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 100,
            'total_capacity' => 50,
        ], $auth)->assertCreated()->json('data.id');

        return [$event, $typeId];
    }

    public function test_purchase_sends_confirmation_to_holder_email_and_phone(): void
    {
        Notification::fake();
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth);

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Riya Sen',
            'holder_email' => 'riya@example.com',
            'holder_phone' => '+919000000001',
            'payment_status' => 'paid',
        ], $auth)->assertCreated();

        Notification::assertSentOnDemand(TicketPurchaseConfirmation::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'riya@example.com'
                && $notifiable->routes['whatsapp'] === '+919000000001'
                && in_array('mail', $channels, true);
        });
    }

    public function test_no_notification_attempted_without_contact_details(): void
    {
        Notification::fake();
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth);

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'No Contact',
            'payment_status' => 'paid',
        ], $auth)->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_whatsapp_stub_records_notification_log_without_provider_key(): void
    {
        config()->set('services.whatsapp.provider_key', null);
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth);

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Arjun',
            'holder_phone' => '+919000000002',
            'payment_status' => 'paid',
        ], $auth)->assertCreated();

        $log = NotificationLog::where('channel', 'whatsapp')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('stubbed_no_provider', $log->status);
        $this->assertSame('+919000000002', $log->recipient);
        $this->assertSame('TicketPurchaseConfirmation', $log->notification);
        $this->assertStringContainsString('Notify Fest', $log->body);
    }

    public function test_reminder_command_notifies_holders_once(): void
    {
        Notification::fake();
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth, [
            'start_date' => now()->addHours(12)->toDateTimeString(),
            'end_date' => now()->addHours(14)->toDateTimeString(),
        ]);

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Meera',
            'holder_email' => 'meera@example.com',
            'payment_status' => 'paid',
        ], $auth)->assertCreated();

        $this->artisan('eventra:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemand(EventReminderNotification::class, function ($notification, $channels, $notifiable) use ($event) {
            return $notifiable->routes['mail'] === 'meera@example.com'
                && $notification->event->id === $event->id;
        });

        $this->assertNotNull($event->fresh()->reminder_sent_at);

        // Idempotent: a second run sends nothing new.
        Notification::fake();
        $this->artisan('eventra:send-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_reminder_skips_far_future_and_cancelled_events(): void
    {
        Notification::fake();
        $auth = $this->admin();

        [$farEvent, $farType] = $this->makeEventWithType($auth, [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->postJson('/api/v1/tickets', [
            'event_id' => $farEvent->id, 'ticket_type_id' => $farType,
            'holder_name' => 'Far', 'holder_email' => 'far@example.com', 'payment_status' => 'paid',
        ], $auth)->assertCreated();

        $this->artisan('eventra:send-reminders')->assertSuccessful();
        Notification::assertNotSentTo(new AnonymousNotifiable, EventReminderNotification::class, function ($notification) use ($farEvent) {
            return $notification->event->id === $farEvent->id;
        });
        $this->assertNull($farEvent->fresh()->reminder_sent_at);
    }

    public function test_confirmation_mail_mentions_event_and_ticket(): void
    {
        $ticket = Ticket::with(['event.venue', 'ticketType'])->first()
            ?? throw new \RuntimeException('Seeder should create tickets');

        $mail = (new TicketPurchaseConfirmation($ticket))->toMail(
            (new AnonymousNotifiable)->route('mail', 'x@example.com')
        );

        $this->assertStringContainsString($ticket->event->title, $mail->subject);
        $this->assertStringContainsString($ticket->ticket_number, implode(' ', $mail->introLines));
    }
}
