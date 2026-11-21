<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSaleCancelledEventTest extends TestCase
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

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function makeEventWithType(array $auth, string $status): array
    {
        $event = Event::findOrFail($this->postJson('/api/v1/events', [
            'title' => 'Ticket Probe',
            'client_id' => Client::firstOrFail()->id,
            'event_category_id' => EventCategory::firstOrFail()->id,
            'event_type' => 'corporate',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'status' => $status,
        ], $auth)->assertCreated()->json('data.id'));

        $typeId = $this->postJson('/api/v1/tickets/types', [
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 100,
            'total_capacity' => 50,
        ], $auth)->assertCreated()->json('data.id');

        return [$event, $typeId];
    }

    public function test_ticket_sale_rejected_for_cancelled_event(): void
    {
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth, 'cancelled');

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Late Buyer',
            'payment_status' => 'paid',
        ], $auth)->assertStatus(422);

        // No ticket, no capacity consumed, no phantom confirmed guests.
        $this->assertSame(0, $event->tickets()->count());
        $this->assertSame(0, (int) $event->fresh()->confirmed_guests);
    }

    public function test_ticket_sale_rejected_for_completed_event(): void
    {
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth, 'completed');

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Late Buyer',
            'payment_status' => 'paid',
        ], $auth)->assertStatus(422);

        $this->assertSame(0, $event->tickets()->count());
        $this->assertSame(0, (int) $event->fresh()->confirmed_guests);
    }

    public function test_ticket_sale_still_works_for_active_event(): void
    {
        $auth = $this->admin();
        [$event, $typeId] = $this->makeEventWithType($auth, 'confirmed');

        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $typeId,
            'holder_name' => 'Happy Buyer',
            'payment_status' => 'paid',
        ], $auth)->assertCreated();

        $this->assertSame(1, $event->tickets()->count());
    }
}
