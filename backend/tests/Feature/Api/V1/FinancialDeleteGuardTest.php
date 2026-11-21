<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDeleteGuardTest extends TestCase
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

    private function makeInvoice(Client $client, array $auth): Invoice
    {
        $response = $this->postJson('/api/v1/invoices', [
            'client_id' => $client->id,
            'invoice_date' => '2026-11-21',
            'due_date' => '2026-12-21',
            'tax_rate' => 0,
            'items' => [['description' => 'Guard probe', 'quantity' => 1, 'unit_price' => 100]],
        ], $auth)->assertCreated();

        return Invoice::findOrFail($response->json('data.id'));
    }

    public function test_client_with_invoices_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $client = Client::findOrFail($this->postJson('/api/v1/clients', ['name' => 'Guard Co', 'client_type' => 'corporate'], $auth)->assertCreated()->json('data.id'));
        $invoice = $this->makeInvoice($client, $auth);

        $this->deleteJson("/api/v1/clients/{$client->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_client_with_quotations_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $client = Client::findOrFail($this->postJson('/api/v1/clients', ['name' => 'Guard Co', 'client_type' => 'corporate'], $auth)->assertCreated()->json('data.id'));
        $this->postJson('/api/v1/quotations', [
            'client_id' => $client->id,
            'issue_date' => '2026-11-21',
            'valid_until' => '2026-12-21',
            'tax_rate' => 0,
            'items' => [['item_name' => 'Guard probe', 'quantity' => 1, 'unit_price' => 100]],
        ], $auth)->assertCreated();

        $this->deleteJson("/api/v1/clients/{$client->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_invoice_with_payments_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $client = Client::findOrFail($this->postJson('/api/v1/clients', ['name' => 'Guard Co', 'client_type' => 'corporate'], $auth)->assertCreated()->json('data.id'));
        $invoice = $this->makeInvoice($client, $auth);
        $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 40,
            'payment_date' => '2026-11-21',
            'payment_method' => 'upi',
        ], $auth)->assertCreated();

        $this->deleteJson("/api/v1/invoices/{$invoice->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id]);
    }

    public function test_client_without_financial_records_can_still_be_deleted(): void
    {
        $auth = $this->admin();
        $client = Client::findOrFail($this->postJson('/api/v1/clients', ['name' => 'Guard Co', 'client_type' => 'corporate'], $auth)->assertCreated()->json('data.id'));

        $this->deleteJson("/api/v1/clients/{$client->id}", [], $auth)->assertOk();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_unpaid_invoice_can_still_be_deleted(): void
    {
        $auth = $this->admin();
        $client = Client::findOrFail($this->postJson('/api/v1/clients', ['name' => 'Guard Co', 'client_type' => 'corporate'], $auth)->assertCreated()->json('data.id'));
        $invoice = $this->makeInvoice($client, $auth);

        $this->deleteJson("/api/v1/invoices/{$invoice->id}", [], $auth)->assertOk();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }
}
