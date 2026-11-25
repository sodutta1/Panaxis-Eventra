<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppNotification;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuotationResponseTest extends TestCase
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

    private function quote(string $status = 'draft', ?string $validUntil = null, ?Client $client = null): Quotation
    {
        $client ??= Client::where('user_id', $this->u('client@eventflow.test')->id)->firstOrFail();
        $admin = $this->u('admin@eventflow.test');
        Sanctum::actingAs($admin);
        $id = $this->postJson('/api/v1/quotations', ['client_id' => $client->id, 'issue_date' => '2026-11-24', 'tax_rate' => 18, 'items' => [['item_name' => 'Stage', 'quantity' => 1, 'unit_price' => 1000]]])->assertCreated()->json('data.id');
        $q = Quotation::findOrFail($id);
        $q->update(array_filter(['status' => $status, 'valid_until' => $validUntil]));

        return $q->refresh();
    }

    public function test_staff_sends_a_draft_and_the_client_is_notified(): void
    {
        $q = $this->quote();
        $this->postJson("/api/v1/quotations/{$q->id}/send")->assertOk()->assertJsonPath('data.status', 'sent');
        $this->assertNotNull($q->refresh()->sent_at);
        $n = AppNotification::where('user_id', $this->u('client@eventflow.test')->id)->where('type', 'quotation_sent')->first();
        $this->assertNotNull($n);
        $this->assertStringContainsString($q->quotation_number, $n->title);
        $this->postJson("/api/v1/quotations/{$q->id}/send")->assertStatus(409);
    }

    public function test_client_accepts_and_sales_staff_are_told(): void
    {
        $q = $this->quote('sent');
        $client = $this->u('client@eventflow.test');
        Sanctum::actingAs($client);
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted'])
            ->assertOk()->assertJsonPath('data.status', 'accepted');
        $q->refresh();
        $this->assertSame('accepted', $q->status);
        $this->assertSame($client->id, (int) $q->responded_by);
        $this->assertNotNull($q->responded_at);
        $n = AppNotification::where('user_id', $this->u('admin@eventflow.test')->id)->where('type', 'quotation_accepted')->first();
        $this->assertNotNull($n);
        $this->assertStringContainsString('accepted', $n->title);
        $this->assertStringContainsString('₹1,180.00', $n->body);
        $this->assertSame(0, AppNotification::where('user_id', $client->id)->where('type', 'quotation_accepted')->count());
    }

    public function test_client_declines_with_a_note(): void
    {
        $q = $this->quote('sent');
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'rejected', 'note' => 'Over budget'])
            ->assertOk()->assertJsonPath('data.response_note', 'Over budget');
        $n = AppNotification::where('type', 'quotation_rejected')->first();
        $this->assertStringContainsString('Over budget', $n->body);
    }

    public function test_cannot_respond_twice_to_a_draft_or_an_expired_quotation(): void
    {
        $q = $this->quote('sent');
        $draft = $this->quote('draft');
        $expired = $this->quote('sent', '2020-01-01');
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted'])->assertOk();
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'rejected'])->assertStatus(409);
        $this->postJson("/api/v1/portal/quotations/{$draft->id}/respond", ['decision' => 'accepted'])->assertStatus(409);
        $this->postJson("/api/v1/portal/quotations/{$expired->id}/respond", ['decision' => 'accepted'])->assertStatus(409);
        $this->assertSame('accepted', $q->refresh()->status);
        $this->assertSame('sent', $expired->refresh()->status);
    }

    public function test_validation(): void
    {
        $q = $this->quote('sent');
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'maybe'])->assertStatus(422);
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", [])->assertStatus(422);
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted', 'note' => str_repeat('x', 501)])->assertStatus(422);
    }

    public function test_only_the_owning_client_can_respond(): void
    {
        $other = Client::where('user_id', '!=', $this->u('client@eventflow.test')->id)->orWhereNull('user_id')->firstOrFail();
        $q = $this->quote('sent', null, $other);
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted'])->assertNotFound();
        $this->assertSame('sent', $q->refresh()->status);
    }

    public function test_staff_roles_cannot_use_the_client_endpoint_and_clients_cannot_send(): void
    {
        $q = $this->quote('sent');
        foreach (['admin@eventflow.test', 'sales@eventflow.test'] as $email) {
            Sanctum::actingAs($this->u($email));
            $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted'])->assertForbidden();
        }
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/quotations/{$q->id}/send")->assertForbidden();
        $this->assertSame('sent', $q->refresh()->status);
    }

    public function test_staff_records_a_verbal_acceptance_honestly(): void
    {
        $q = $this->quote('draft');
        $sales = $this->u('sales@eventflow.test');
        Sanctum::actingAs($sales);
        $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'accepted', 'method' => 'phone', 'note' => 'Agreed on call with the client'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.response_source', 'staff_recorded')
            ->assertJsonPath('data.response_method', 'phone')
            ->assertJsonPath('data.responded_by_name', $sales->name);
        $q->refresh();
        $this->assertSame($sales->id, (int) $q->responded_by);
        $this->assertNotNull($q->sent_at);
        $n = AppNotification::where('user_id', $this->u('admin@eventflow.test')->id)->where('type', 'quotation_accepted')->first();
        $this->assertStringContainsString($sales->name.' recorded that', $n->body);
        $this->assertSame(0, AppNotification::where('user_id', $sales->id)->where('type', 'quotation_accepted')->count());
        $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'rejected', 'method' => 'phone'])->assertStatus(409);
    }

    public function test_client_portal_response_is_marked_as_client_portal(): void
    {
        $q = $this->quote('sent');
        Sanctum::actingAs($this->u('client@eventflow.test'));
        $this->postJson("/api/v1/portal/quotations/{$q->id}/respond", ['decision' => 'accepted'])
            ->assertOk()->assertJsonPath('data.response_source', 'client_portal');
    }

    public function test_record_response_validation_and_permissions(): void
    {
        $q = $this->quote('sent');
        Sanctum::actingAs($this->u('admin@eventflow.test'));
        $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'accepted'])->assertStatus(422);
        $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'accepted', 'method' => 'telepathy'])->assertStatus(422);
        foreach (['client@eventflow.test', 'finance@eventflow.test'] as $email) {
            Sanctum::actingAs($this->u($email));
            $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'accepted', 'method' => 'verbal'])->assertForbidden();
        }
        $this->assertSame('sent', $q->refresh()->status);
    }

    public function test_recording_works_after_expiry_because_staff_heard_it_directly(): void
    {
        $q = $this->quote('sent', '2020-01-01');
        Sanctum::actingAs($this->u('admin@eventflow.test'));
        $this->postJson("/api/v1/quotations/{$q->id}/record-response", ['decision' => 'accepted', 'method' => 'in_person'])->assertOk();
    }
}
