<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckInTest extends TestCase
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

    private function makeEventWithGuest(array $auth, string $status = 'confirmed'): array
    {
        $event = Event::findOrFail($this->postJson('/api/v1/events', [
            'title' => 'Checkin Probe',
            'client_id' => Client::firstOrFail()->id,
            'event_category_id' => EventCategory::firstOrFail()->id,
            'event_type' => 'corporate',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'status' => $status,
        ], $auth)->assertCreated()->json('data.id'));

        $guest = EventGuest::findOrFail($this->postJson('/api/v1/guests', [
            'event_id' => $event->id,
            'name' => 'Gate Probe',
            'dietary_preference' => 'none',
        ], $auth)->assertCreated()->json('data.id'));

        return [$event, $guest];
    }

    public function test_checkin_is_one_way_and_reports_duplicate(): void
    {
        $auth = $this->admin();
        [, $guest] = $this->makeEventWithGuest($auth);

        $this->postJson("/api/v1/guests/{$guest->id}/checkin", [], $auth)
            ->assertOk()
            ->assertJsonPath('data.checked_in', 1); // tinyint, no boolean cast on the model

        $this->postJson("/api/v1/guests/{$guest->id}/checkin", [], $auth)
            ->assertStatus(409);

        // A retry at the gate must never un-admit the guest.
        $this->assertTrue((bool) $guest->fresh()->checked_in);
        $this->assertNotNull($guest->fresh()->checked_in_at);
    }

    public function test_checkin_rejected_for_cancelled_event(): void
    {
        $auth = $this->admin();
        [, $guest] = $this->makeEventWithGuest($auth, 'cancelled');

        $this->postJson("/api/v1/guests/{$guest->id}/checkin", [], $auth)
            ->assertStatus(422);

        $this->assertFalse((bool) $guest->fresh()->checked_in);
    }
}
