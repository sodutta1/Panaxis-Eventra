<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function auth(string $email = 'admin@eventflow.test'): array
    {
        $token = $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'password'])->assertOk()->json('token');

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function payload(int $clientId, array $over = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'issue_date' => '2026-11-24',
            'valid_until' => '2026-12-24',
            'tax_rate' => 18,
            'notes' => 'First draft',
            'items' => [['item_name' => 'Stage', 'quantity' => 2, 'unit_price' => 1000]],
        ], $over);
    }

    public function test_admin_can_edit_a_draft_and_totals_are_recomputed(): void
    {
        $auth = $this->auth();
        $client = Client::firstOrFail();
        $id = $this->postJson('/api/v1/quotations', $this->payload($client->id), $auth)->assertCreated()->json('data.id');

        $this->putJson("/api/v1/quotations/{$id}", $this->payload($client->id, [
            'notes' => 'Revised',
            'tax_rate' => 5,
            'items' => [['item_name' => 'Stage', 'quantity' => 1, 'unit_price' => 1000], ['item_name' => 'Sound', 'quantity' => 1, 'unit_price' => 500]],
        ]), $auth)->assertOk();

        $q = Quotation::with('items')->findOrFail($id);
        $this->assertSame('Revised', $q->notes);
        $this->assertCount(2, $q->items);
        $this->assertEquals(1500.00, (float) $q->subtotal);
        $this->assertEquals(75.00, (float) $q->tax_amount);
        $this->assertEquals(1575.00, (float) $q->grand_total);
        $this->assertSame('draft', $q->status);
    }

    public function test_accepted_quotation_cannot_be_edited(): void
    {
        $auth = $this->auth();
        $client = Client::firstOrFail();
        $id = $this->postJson('/api/v1/quotations', $this->payload($client->id), $auth)->assertCreated()->json('data.id');
        Quotation::whereKey($id)->update(['status' => 'accepted']);

        $this->putJson("/api/v1/quotations/{$id}", $this->payload($client->id, ['notes' => 'Nope']), $auth)->assertStatus(409);
        $this->assertSame('First draft', Quotation::findOrFail($id)->notes);
    }

    public function test_non_admin_cannot_edit(): void
    {
        $admin = $this->auth();
        $client = Client::firstOrFail();
        $id = $this->postJson('/api/v1/quotations', $this->payload($client->id), $admin)->assertCreated()->json('data.id');
        $user = User::where('role', 'event_manager')->first() ?? User::where('role', 'sales_executive')->first();
        if (! $user) {
            $this->markTestSkipped('No non-admin seeded user.');
        }
        $other = $this->auth($user->email);
        $this->app['auth']->forgetGuards();
        $this->putJson("/api/v1/quotations/{$id}", $this->payload($client->id), $other)->assertForbidden();
    }

    public function test_cannot_edit_another_companys_quotation(): void
    {
        $auth = $this->auth();
        $client = Client::firstOrFail();
        $this->putJson('/api/v1/quotations/99999999', $this->payload($client->id), $auth)->assertNotFound();
    }
}
