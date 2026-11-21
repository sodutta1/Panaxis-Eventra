<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDateTest extends TestCase
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

    private function makeEvent(array $auth, string $start = '2026-12-01', string $end = '2026-12-03'): Event
    {
        return Event::findOrFail($this->postJson('/api/v1/events', [
            'title' => 'Schedule Probe',
            'client_id' => Client::firstOrFail()->id,
            'event_category_id' => EventCategory::firstOrFail()->id,
            'event_type' => 'corporate',
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'confirmed',
        ], $auth)->assertCreated()->json('data.id'));
    }

    public function test_schedule_date_is_mapped_to_the_matching_event_day(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $day = $event->days()->firstOrFail();

        $res = $this->postJson("/api/v1/events/{$event->id}/schedules", [
            'schedule_date' => '2026-12-01',
            'start_time' => '11:00',
            'title' => 'Panel',
        ], $auth)->assertCreated();

        // The date must be persisted via the event day, not dropped.
        $this->assertSame($day->id, (int) $res->json('data.event_day_id'));
        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'event_day_id' => $day->id,
            'title' => 'Panel',
        ]);
    }

    public function test_schedule_date_without_a_matching_event_day_is_rejected(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);

        $this->postJson("/api/v1/events/{$event->id}/schedules", [
            'schedule_date' => '2026-12-02', // event only has day 1 (2026-12-01)
            'start_time' => '11:00',
            'title' => 'Panel',
        ], $auth)->assertStatus(422);

        $this->assertSame(0, $event->schedules()->count());
    }

    public function test_event_day_id_from_another_event_is_rejected(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);
        $other = $this->makeEvent($auth, '2026-12-10', '2026-12-11');
        $foreignDay = $other->days()->firstOrFail();

        $this->postJson("/api/v1/events/{$event->id}/schedules", [
            'event_day_id' => $foreignDay->id,
            'start_time' => '11:00',
            'title' => 'Panel',
        ], $auth)->assertStatus(422);

        $this->assertSame(0, $event->schedules()->count());
    }

    public function test_schedule_without_date_keeps_existing_null_day_behavior(): void
    {
        $auth = $this->admin();
        $event = $this->makeEvent($auth);

        $this->postJson("/api/v1/events/{$event->id}/schedules", [
            'start_time' => '11:00',
            'title' => 'Panel',
        ], $auth)->assertCreated()->assertJsonPath('data.event_day_id', null);
    }
}
