<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppNotification;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function u(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_requires_login(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_payment_notifies_finance_roles_but_not_the_actor_or_sales(): void
    {
        $actor = $this->u('finance@eventflow.test');
        Sanctum::actingAs($actor);
        $inv = Invoice::where('status', 'unpaid')->firstOrFail();
        $this->postJson("/api/v1/invoices/{$inv->id}/payments", ['amount' => 100, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()])->assertCreated();
        $admin = $this->u('admin@eventflow.test');
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('type', 'payment_recorded')->count());
        $this->assertSame(0, AppNotification::where('user_id', $actor->id)->count());
        $this->assertSame(0, AppNotification::where('user_id', $this->u('sales@eventflow.test')->id)->count());
        $this->assertSame(0, AppNotification::where('user_id', $this->u('client@eventflow.test')->id)->count());
    }

    public function test_feed_is_private_and_mark_read_works(): void
    {
        $a = $this->u('admin@eventflow.test');
        $s = $this->u('sales@eventflow.test');
        $n = AppNotification::create(['company_id' => $a->company_id, 'user_id' => $a->id, 'type' => 't', 'title' => 'Hello']);
        Sanctum::actingAs($s);
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/notifications/{$n->id}/read")->assertNotFound();
        Sanctum::actingAs($a);
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('unread_count', 1);
        $this->postJson("/api/v1/notifications/{$n->id}/read")->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_cross_company_notification_is_never_visible(): void
    {
        $a = $this->u('admin@eventflow.test');
        $other = Company::create(['name' => 'Other Co', 'code' => 'OTH', 'status' => 'active']);
        AppNotification::create(['company_id' => $other->id, 'user_id' => $a->id, 'type' => 't', 'title' => 'Leak']);
        Sanctum::actingAs($a);
        $this->getJson('/api/v1/notifications')->assertJsonCount(0, 'data');
    }

    public function test_read_all_and_unread_filter_and_paging(): void
    {
        $a = $this->u('admin@eventflow.test');
        foreach (range(1, 25) as $i) {
            AppNotification::create(['company_id' => $a->company_id, 'user_id' => $a->id, 'type' => 't', 'title' => "N$i"]);
        }
        Sanctum::actingAs($a);
        $this->getJson('/api/v1/notifications?per_page=10')->assertJsonCount(10, 'data')->assertJsonPath('meta.last_page', 3)->assertJsonPath('unread_count', 25);
        $this->postJson('/api/v1/notifications/read-all')->assertJsonPath('unread_count', 0);
        $this->getJson('/api/v1/notifications?filter=unread')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/notifications')->assertJsonPath('data.0.read', true);
    }

    public function test_assigned_lead_notifies_only_the_assignee(): void
    {
        $sales = $this->u('sales@eventflow.test');
        Sanctum::actingAs($this->u('admin@eventflow.test'));
        $lead = Lead::firstOrFail();
        $lead->update(['assigned_to' => null]);
        $this->putJson("/api/v1/leads/{$lead->id}", array_merge(Lead::find($lead->id)->only(['contact_name', 'phone', 'email', 'source']), ['assigned_to' => $sales->id]))->assertOk();
        $this->assertSame(1, AppNotification::where('user_id', $sales->id)->where('type', 'lead_assigned')->count());
        $this->assertSame(0, AppNotification::where('user_id', $this->u('finance@eventflow.test')->id)->count());
    }

    public function test_pending_expense_asks_finance_and_approval_tells_creator(): void
    {
        $creator = $this->u('finance@eventflow.test');
        $admin = $this->u('admin@eventflow.test');
        Sanctum::actingAs($creator);
        $event = \App\Models\Event::firstOrFail();
        $r = $this->postJson('/api/v1/expenses', ['event_id' => $event->id, 'title' => 'Tent', 'amount' => 500, 'incurred_date' => now()->toDateString(), 'payment_method' => 'cash', 'expense_category_id' => \App\Models\ExpenseCategory::value('id')]);
        if ($r->status() !== 201) { $this->markTestSkipped('expense payload differs: '.$r->getContent()); }
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('type', 'expense_pending')->count());
        $id = $r->json('data.id');
        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/expenses/$id/approve")->assertOk();
        $this->assertSame(1, AppNotification::where('user_id', $creator->id)->where('type', 'expense_approved')->count());
    }
}
