<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDeleteGuardTest extends TestCase
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

    private function makeEvent(array $auth): Event
    {
        $id = $this->postJson('/api/v1/events', [
            'title' => 'Guard Event',
            'client_id' => Client::firstOrFail()->id,
            'event_type' => 'corporate',
            'event_category_id' => EventCategory::firstOrFail()->id,
            'venue_id' => Venue::firstOrFail()->id,
            'start_date' => '2027-05-10',
            'end_date' => '2027-05-11',
            'expected_guests' => 100,
            'estimated_budget' => 10000,
            'contract_value' => 20000,
            'status' => 'confirmed',
        ], $auth)->assertCreated()->json('data.id');

        return Event::findOrFail($id);
    }

    public function test_event_with_guests_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $guestId = $this->postJson('/api/v1/guests', [
            'event_id' => $event->id, 'name' => 'Guard Guest', 'dietary_preference' => 'none',
        ], $auth)->assertCreated()->json('data.id');

        $this->deleteJson("/api/v1/events/{$event->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertDatabaseHas('event_guests', ['id' => $guestId]);
    }

    public function test_event_with_sold_tickets_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $typeId = $this->postJson('/api/v1/tickets/types', [
            'event_id' => $event->id, 'name' => 'GA', 'price' => 100, 'total_capacity' => 10,
        ], $auth)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/tickets', [
            'event_id' => $event->id, 'ticket_type_id' => $typeId, 'quantity' => 1,
            'holder_name' => 'Guard Buyer', 'payment_status' => 'paid', 'price_paid' => 100,
        ], $auth)->assertCreated();

        $this->deleteJson("/api/v1/events/{$event->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertDatabaseHas('tickets', ['event_id' => $event->id]);
    }

    public function test_event_with_budget_items_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $this->postJson("/api/v1/events/{$event->id}/budget/items", [
            'category' => 'decor', 'item_name' => 'Guard item', 'estimated_cost' => 500,
        ], $auth)->assertCreated();

        $this->deleteJson("/api/v1/events/{$event->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_event_with_work_orders_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $this->postJson("/api/v1/events/{$event->id}/work-orders", [
            'vendor_id' => \App\Models\Vendor::firstOrFail()->id, 'service_title' => 'Guard WO', 'contract_amount' => 1000,
        ], $auth)->assertCreated();

        $this->deleteJson("/api/v1/events/{$event->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertDatabaseHas('vendor_assignments', ['event_id' => $event->id]);
    }

    public function test_empty_event_can_still_be_deleted(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);

        $this->deleteJson("/api/v1/events/{$event->id}", [], $auth)->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
