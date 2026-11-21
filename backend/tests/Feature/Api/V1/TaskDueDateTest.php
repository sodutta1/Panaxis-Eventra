<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDueDateTest extends TestCase
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

    public function test_past_due_date_is_rejected(): void
    {
        $auth = $this->admin();
        $eventId = Event::firstOrFail()->id;

        $this->postJson("/api/v1/events/{$eventId}/tasks", [
            'title' => 'Backdated task',
            'priority' => 'high',
            'due_date' => '2020-01-01',
        ], $auth)->assertStatus(422)->assertJsonValidationErrors(['due_date']);

        $this->assertSame(0, $event = Event::firstOrFail()->tasks()->where('title', 'Backdated task')->count());
    }

    public function test_today_and_future_due_dates_are_accepted(): void
    {
        $auth = $this->admin();
        $eventId = Event::firstOrFail()->id;

        $this->postJson("/api/v1/events/{$eventId}/tasks", [
            'title' => 'Due today',
            'priority' => 'high',
            'due_date' => date('Y-m-d'),
        ], $auth)->assertCreated();

        $this->postJson("/api/v1/events/{$eventId}/tasks", [
            'title' => 'Due later',
            'priority' => 'low',
            'due_date' => date('Y-m-d', strtotime('+30 days')),
        ], $auth)->assertCreated();
    }
}
