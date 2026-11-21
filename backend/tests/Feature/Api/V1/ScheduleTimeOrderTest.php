<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTimeOrderTest extends TestCase
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

    public function test_schedule_cannot_end_before_it_starts(): void
    {
        $auth = $this->admin();

        $this->postJson('/api/v1/events/' . Event::firstOrFail()->id . '/schedules', [
            'start_time' => '18:00',
            'end_time' => '09:00',
            'title' => 'Time Warp',
        ], $auth)->assertStatus(422)->assertJsonValidationErrors('end_time');

        $this->assertDatabaseMissing('event_schedules', ['title' => 'Time Warp']);
    }

    public function test_schedule_with_sane_times_is_still_accepted(): void
    {
        $auth = $this->admin();

        $this->postJson('/api/v1/events/' . Event::firstOrFail()->id . '/schedules', [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'title' => 'Sane Slot',
        ], $auth)->assertCreated();

        $this->assertDatabaseHas('event_schedules', ['title' => 'Sane Slot']);
    }
}
