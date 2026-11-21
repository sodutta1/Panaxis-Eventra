<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tokenFor(string $email): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password',
        ]);
        $response->assertOk()->assertJsonStructure(['token', 'user']);

        return $response->json('token');
    }

    private function auth(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_login_issues_token_for_seeded_admin(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@eventflow.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role']])
            ->assertJsonPath('user.role', 'super_admin');
    }

    public function test_login_rejects_bad_password(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@eventflow.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/events')->assertStatus(401);
    }

    public function test_events_index_returns_seeded_events(): void
    {
        $token = $this->tokenFor('manager@eventflow.test');

        $response = $this->getJson('/api/v1/events', $this->auth($token));

        $response->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertGreaterThanOrEqual(7, $response->json('meta.total'));
    }

    public function test_event_create_and_status_transition(): void
    {
        $token = $this->tokenFor('manager@eventflow.test');

        $client = \App\Models\Client::firstOrFail();
        $category = \App\Models\EventCategory::firstOrFail();

        $create = $this->postJson('/api/v1/events', [
            'title' => 'API Test Summit',
            'client_id' => $client->id,
            'event_category_id' => $category->id,
            'event_type' => 'corporate',
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-11',
            'status' => 'proposed',
        ], $this->auth($token));

        $create->assertStatus(201)->assertJsonPath('data.title', 'API Test Summit');
        $eventId = $create->json('data.id');
        $this->assertStringStartsWith('EVT-', $create->json('data.event_code'));

        $this->postJson("/api/v1/events/{$eventId}/status", ['status' => 'confirmed'], $this->auth($token))
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
    }

    public function test_rbac_blocks_vendor_role_from_staff_endpoints(): void
    {
        $token = $this->tokenFor('vendor@eventflow.test');

        $this->getJson('/api/v1/leads', $this->auth($token))->assertStatus(403);
        $this->getJson('/api/v1/events', $this->auth($token))->assertStatus(403);
    }

    public function test_rbac_blocks_sales_from_finance_writes(): void
    {
        $token = $this->tokenFor('sales@eventflow.test');

        $invoice = \App\Models\Invoice::firstOrFail();

        $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 100,
            'payment_date' => '2026-11-20',
            'payment_method' => 'upi',
        ], $this->auth($token))->assertStatus(403);
    }

    public function test_client_role_can_only_use_client_portal(): void
    {
        $token = $this->tokenFor('client@eventflow.test');

        $user = \App\Models\User::where('email', 'client@eventflow.test')->firstOrFail();

        $this->getJson('/api/v1/portal/client', $this->auth($token))
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id);

        $this->getJson('/api/v1/dashboard', $this->auth($token))->assertStatus(403);
    }

    public function test_tenant_isolation_hides_other_companies_records(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Tenant Co',
            'code' => 'OTC',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Admin',
            'email' => 'other-admin@example.com',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $token = $otherUser->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/events', $this->auth($token))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        // Cross-tenant record access resolves as 404, never data
        $event = Event::first();
        $this->getJson('/api/v1/events/' . $event->id, $this->auth($token))->assertStatus(404);
    }

    public function test_payment_reduces_invoice_balance_and_caps_overpayment(): void
    {
        $token = $this->tokenFor('finance@eventflow.test');
        $invoice = \App\Models\Invoice::where('status', 'unpaid')->firstOrFail();
        $half = round(((float) $invoice->balance_due) / 2, 2);

        $pay = $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => $half,
            'payment_date' => '2026-11-20',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'UTR-TEST-001',
        ], $this->auth($token));

        $pay->assertStatus(201)
            ->assertJsonPath('invoice.status', 'partially_paid');

        // Overpayment beyond balance_due is rejected by the Form Request
        $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 999999999,
            'payment_date' => '2026-11-20',
            'payment_method' => 'bank_transfer',
        ], $this->auth($token))->assertStatus(422);
    }

    public function test_gate_scan_verify_marks_ticket_checked_in_once(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');
        $ticket = Ticket::where('ticket_status', 'valid')->firstOrFail();

        $this->postJson('/api/v1/tickets/verify', ['token' => $ticket->qr_token], $this->auth($token))
            ->assertOk()->assertJsonPath('status', 'success');

        // Second scan of the same ticket is a 409 ALREADY ADMITTED
        $this->postJson('/api/v1/tickets/verify', ['token' => $ticket->qr_token], $this->auth($token))
            ->assertStatus(409)->assertJsonPath('status', 'warning');
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');

        $user = \App\Models\User::where('email', 'admin@eventflow.test')->first();
        $this->assertGreaterThan(0, $user->tokens()->count());

        $this->postJson('/api/v1/auth/logout', [], $this->auth($token))->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_expense_guest_and_ticket_show_endpoints(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');

        foreach (['expenses', 'guests', 'tickets'] as $resource) {
            $id = $this->getJson("/api/v1/{$resource}", $this->auth($token))->json('data.0.id');
            $this->assertNotNull($id, "no seeded {$resource}");
            $this->getJson("/api/v1/{$resource}/{$id}", $this->auth($token))
                ->assertOk()
                ->assertJsonStructure(['data' => ['id']]);
        }
    }

    public function test_budget_items_recompute_totals(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');
        $eventId = $this->getJson('/api/v1/events', $this->auth($token))->json('data.0.id');

        $this->postJson("/api/v1/events/{$eventId}/budget/items", [
            'category' => 'Decor', 'item_name' => 'Stage flowers', 'estimated_cost' => 50000,
            'actual_cost' => null, 'notes' => null,
        ], $this->auth($token))->assertCreated();

        $this->postJson("/api/v1/events/{$eventId}/budget/items", [
            'category' => 'Sound', 'item_name' => 'PA system', 'estimated_cost' => 30000, 'actual_cost' => 28000,
        ], $this->auth($token))->assertCreated();

        $budget = $this->getJson("/api/v1/events/{$eventId}/budget", $this->auth($token))->json('data');
        $this->assertCount(2, $budget['items']);
        $this->assertEquals(80000, (float) $budget['total_estimated_cost']);
        $this->assertEquals(28000, (float) $budget['total_actual_cost']);
    }

    public function test_work_order_payment_rejects_overpayment(): void
    {
        // Updated by fix(EV-QA-007): overpayment is a 422, not a silent cap.
        $token = $this->tokenFor('admin@eventflow.test');
        $eventId = $this->getJson('/api/v1/events', $this->auth($token))->json('data.0.id');
        $vendorId = $this->getJson('/api/v1/vendors', $this->auth($token))->json('data.0.id');

        $wo = $this->postJson("/api/v1/events/{$eventId}/work-orders", [
            'vendor_id' => $vendorId, 'service_title' => 'Catering', 'contract_amount' => 100000,
        ], $this->auth($token))->assertCreated()->json('data');

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 150000], $this->auth($token))
            ->assertStatus(422);

        $res = $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 100000], $this->auth($token))
            ->assertOk()->json('data');
        $this->assertEquals(100000, (float) $res['paid_amount']);
        $this->assertSame('paid', $res['status']);
    }

    public function test_admin_endpoints_reject_non_admin(): void
    {
        $salesToken = $this->tokenFor('sales@eventflow.test');
        $this->getJson('/api/v1/admin/users', $this->auth($salesToken))->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $adminToken = $this->tokenFor('admin@eventflow.test');
        $this->getJson('/api/v1/admin/users', $this->auth($adminToken))->assertOk()->assertJsonStructure(['data', 'roles']);
        $this->postJson('/api/v1/admin/users', [
            'name' => 'Test Staff', 'email' => 'teststaff@eventflow.test', 'password' => 'secret-pass-1', 'role' => 'staff',
        ], $this->auth($adminToken))->assertCreated();
    }

    public function test_invoice_and_quotation_pdf_download(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');
        $invoiceId = $this->getJson('/api/v1/invoices', $this->auth($token))->json('data.0.id');
        $quotationId = $this->getJson('/api/v1/quotations', $this->auth($token))->json('data.0.id');

        $inv = $this->get("/api/v1/invoices/{$invoiceId}/pdf", $this->auth($token));
        $inv->assertOk();
        $this->assertSame('application/pdf', $inv->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $inv->getContent());

        $qtn = $this->get("/api/v1/quotations/{$quotationId}/pdf", $this->auth($token));
        $qtn->assertOk();
        $this->assertStringStartsWith('%PDF', $qtn->getContent());
    }

    public function test_vendor_categories_endpoint_lists_seeded_categories(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');

        $response = $this->getJson('/api/v1/vendor-categories', $this->auth($token));

        $response->assertOk()->assertJsonStructure(['data']);
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    public function test_expense_update_and_destroy(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');
        $id = $this->getJson('/api/v1/expenses', $this->auth($token))->json('data.0.id');
        $this->assertNotNull($id, 'no seeded expenses');

        $this->patchJson("/api/v1/expenses/{$id}", ['title' => 'Updated expense title'], $this->auth($token))
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated expense title');

        $this->deleteJson("/api/v1/expenses/{$id}", [], $this->auth($token))->assertOk();
        $this->getJson("/api/v1/expenses/{$id}", $this->auth($token))->assertNotFound();
    }

    public function test_guest_update_and_destroy(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');
        $id = $this->getJson('/api/v1/guests', $this->auth($token))->json('data.0.id');
        $this->assertNotNull($id, 'no seeded guests');

        $this->patchJson("/api/v1/guests/{$id}", ['rsvp_status' => 'confirmed', 'table_number' => 'T9'], $this->auth($token))
            ->assertOk()
            ->assertJsonPath('data.rsvp_status', 'confirmed')
            ->assertJsonPath('data.table_number', 'T9');

        $this->deleteJson("/api/v1/guests/{$id}", [], $this->auth($token))->assertOk();
        $this->getJson("/api/v1/guests/{$id}", $this->auth($token))->assertNotFound();
    }

    public function test_ticket_types_listing_and_ticket_update_destroy(): void
    {
        $token = $this->tokenFor('admin@eventflow.test');

        $types = $this->getJson('/api/v1/tickets/types', $this->auth($token));
        $types->assertOk()->assertJsonStructure(['data']);
        $this->assertGreaterThanOrEqual(1, count($types->json('data')));

        $id = $this->getJson('/api/v1/tickets', $this->auth($token))->json('data.0.id');
        $this->assertNotNull($id, 'no seeded tickets');

        $this->patchJson("/api/v1/tickets/{$id}", ['holder_name' => 'Updated Holder'], $this->auth($token))
            ->assertOk()
            ->assertJsonPath('data.holder_name', 'Updated Holder');

        $this->deleteJson("/api/v1/tickets/{$id}", [], $this->auth($token))->assertOk();
        $this->getJson("/api/v1/tickets/{$id}", $this->auth($token))->assertNotFound();
    }

    public function test_guest_delete_requires_admin(): void
    {
        $token = $this->tokenFor('manager@eventflow.test');
        $id = $this->getJson('/api/v1/guests', $this->auth($token))->json('data.0.id');

        $this->deleteJson("/api/v1/guests/{$id}", [], $this->auth($token))->assertForbidden();
    }

}
