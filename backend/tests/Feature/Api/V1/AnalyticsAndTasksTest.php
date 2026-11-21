<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnalyticsAndTasksTest extends TestCase
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
            'email' => 'admin@eventflow.test', 'password' => 'password',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_analytics_returns_all_widget_payloads(): void
    {
        $auth = $this->admin();
        $data = $this->getJson('/api/v1/analytics', $auth)->assertOk()->json('data');

        foreach (['kpis', 'task_completion', 'idle_tasks_by_category', 'overdue_tasks_by_category',
            'leads_funnel', 'top_cost_categories', 'vendor_burn_rate', 'clients'] as $key) {
            $this->assertArrayHasKey($key, $data, "missing widget: $key");
        }
        $this->assertArrayHasKey('events_this_week', $data['kpis']);
        $this->assertArrayHasKey('rate', $data['task_completion']);
        $this->assertArrayHasKey('repeat', $data['clients']);
        $this->assertArrayHasKey('churned', $data['clients']);
    }

    public function test_tasks_grouped_by_phase(): void
    {
        $auth = $this->admin();
        $event = Event::firstOrFail();

        $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Pre event setup', 'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high', 'phase' => 'pre_event',
        ], $auth)->assertCreated()->assertJsonPath('data.phase', 'pre_event');

        $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Gate duty', 'due_date' => now()->addDay()->toDateString(),
            'priority' => 'urgent', 'phase' => 'on_event',
        ], $auth)->assertCreated();

        $data = $this->getJson('/api/v1/events/'.$event->id.'/tasks', $auth)->assertOk()->json('data');
        $this->assertArrayHasKey('pre_event', $data);
        $this->assertArrayHasKey('on_event', $data);
        $this->assertArrayHasKey('post_event', $data);
        $this->assertSame('Gate duty', collect($data['on_event'])->firstWhere('title', 'Gate duty')['title']);
    }

    public function test_dependency_blocks_completion_until_dependency_done(): void
    {
        $auth = $this->admin();
        $event = Event::firstOrFail();

        $first = $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Book venue', 'due_date' => now()->addDay()->toDateString(), 'priority' => 'high',
        ], $auth)->assertCreated()->json('data.id');

        $second = $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Print passes', 'due_date' => now()->addDay()->toDateString(),
            'priority' => 'medium', 'depends_on' => $first,
        ], $auth)->assertCreated()->json('data');

        $this->assertTrue($second['blocked']);

        $this->postJson('/api/v1/tasks/'.$second['id'].'/toggle', [], $auth)->assertStatus(422);

        $this->postJson('/api/v1/tasks/'.$first.'/toggle', [], $auth)->assertOk();
        $this->postJson('/api/v1/tasks/'.$second['id'].'/toggle', [], $auth)->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_dependency_must_be_same_event_and_not_circular(): void
    {
        $auth = $this->admin();
        $event = Event::firstOrFail();

        $a = $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Task A', 'due_date' => now()->addDay()->toDateString(), 'priority' => 'low',
        ], $auth)->assertCreated()->json('data.id');

        $b = $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Task B', 'due_date' => now()->addDay()->toDateString(),
            'priority' => 'low', 'depends_on' => $a,
        ], $auth)->assertCreated()->json('data.id');

        // A -> B would be circular
        $this->patchJson('/api/v1/tasks/'.$a, [
            'title' => 'Task A', 'due_date' => now()->addDay()->toDateString(),
            'priority' => 'low', 'depends_on' => $b,
        ], $auth)->assertStatus(422);

        // Other event's task cannot be a dependency
        $otherEvent = Event::where('id', '!=', $event->id)->first();
        if ($otherEvent) {
            $otherTask = $this->postJson('/api/v1/events/'.$otherEvent->id.'/tasks', [
                'title' => 'Other task', 'due_date' => now()->addDay()->toDateString(), 'priority' => 'low',
            ], $auth)->assertCreated()->json('data.id');

            $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
                'title' => 'Bad dep', 'due_date' => now()->addDay()->toDateString(),
                'priority' => 'low', 'depends_on' => $otherTask,
            ], $auth)->assertStatus(422);
        }
    }

    public function test_task_due_reminder_notifies_assignee_once(): void
    {
        Notification::fake();
        $auth = $this->admin();
        $event = Event::firstOrFail();
        $user = User::where('company_id', $event->company_id)->firstOrFail();

        $this->postJson('/api/v1/events/'.$event->id.'/tasks', [
            'title' => 'Due soon', 'due_date' => now()->addHours(12)->toDateString(),
            'priority' => 'high', 'assigned_to' => $user->id,
        ], $auth)->assertCreated();

        $this->artisan('eventra:send-task-reminders')->assertSuccessful();
        Notification::assertSentTo($user, TaskDueReminderNotification::class);

        Notification::fake();
        $this->artisan('eventra:send-task-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }
}
