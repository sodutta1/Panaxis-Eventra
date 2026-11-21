<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusValidationTest extends TestCase
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

    private function makeEvent(array $auth, string $status = 'in_planning'): Event
    {
        $client = \App\Models\Client::firstOrFail();
        $category = \App\Models\EventCategory::firstOrFail();
        $response = $this->postJson('/api/v1/events', [
            'title' => 'Status Probe',
            'client_id' => $client->id,
            'event_category_id' => $category->id,
            'event_type' => 'corporate',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'status' => $status,
        ], $auth)->assertCreated();

        return Event::findOrFail($response->json('data.id'));
    }

    public function test_status_endpoint_rejects_unknown_status(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);

        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'bogus-status'], $auth)
            ->assertStatus(422);

        $this->assertSame('in_planning', $event->fresh()->status);
    }

    public function test_put_rejects_unknown_status(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);

        $this->putJson("/api/v1/events/{$event->id}", ['status' => 'nonsense-status'], $auth)
            ->assertStatus(422);

        $this->assertSame('in_planning', $event->fresh()->status);
    }

    public function test_create_rejects_unknown_status(): void
    {
        $auth = $this->admin();
        $client = \App\Models\Client::firstOrFail();
        $category = \App\Models\EventCategory::firstOrFail();

        $this->postJson('/api/v1/events', [
            'title' => 'Bad Status Event',
            'client_id' => $client->id,
            'event_category_id' => $category->id,
            'event_type' => 'corporate',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'status' => 'bogus-status',
        ], $auth)->assertStatus(422);
    }

    public function test_backwards_transition_rejected(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth, 'confirmed');

        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'completed'], $auth)->assertOk();
        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'in_planning'], $auth)
            ->assertStatus(422);

        $this->assertSame('completed', $event->fresh()->status);
    }

    public function test_forward_transitions_and_cancellation_allowed(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth, 'proposed');

        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'in_planning'], $auth)->assertOk();
        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'confirmed'], $auth)->assertOk();
        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'cancelled'], $auth)->assertOk();
        $this->postJson("/api/v1/events/{$event->id}/status", ['status' => 'confirmed'], $auth)->assertStatus(422);
    }

    public function test_put_with_unchanged_status_still_works(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth, 'confirmed');

        $this->putJson("/api/v1/events/{$event->id}", ['status' => 'confirmed', 'title' => 'Renamed'], $auth)
            ->assertOk()
            ->assertJsonPath('data.title', 'Renamed');
    }
}
