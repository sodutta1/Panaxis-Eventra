<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderPaymentTest extends TestCase
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

    private function makeWorkOrder(array $auth, float $contract = 100): array
    {
        $eventId = Event::firstOrFail()->id;
        $vendorId = Vendor::firstOrFail()->id;

        return $this->postJson("/api/v1/events/{$eventId}/work-orders", [
            'vendor_id' => $vendorId,
            'service_title' => 'Payment Probe',
            'contract_amount' => $contract,
        ], $auth)->assertCreated()->json('data');
    }

    public function test_overpayment_is_rejected_with_422_and_nothing_recorded(): void
    {
        $auth = $this->admin();
        $wo = $this->makeWorkOrder($auth, 100);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 150], $auth)
            ->assertStatus(422);

        $orders = collect($this->getJson("/api/v1/events/{$wo['event_id']}/work-orders", $auth)
            ->assertOk()->json('data'));
        $fresh = $orders->firstWhere('id', $wo['id']);
        $this->assertNotNull($fresh);
        $this->assertSame(0.0, (float) $fresh['paid_amount']);
        $this->assertSame('assigned', $fresh['status']);
    }

    public function test_overpayment_after_partial_payment_is_rejected(): void
    {
        $auth = $this->admin();
        $wo = $this->makeWorkOrder($auth, 100);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 60], $auth)
            ->assertOk();

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 41], $auth)
            ->assertStatus(422);

        // Exactly the remaining balance still goes through.
        $res = $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 40], $auth)
            ->assertOk()->json('data');
        $this->assertSame(100.0, (float) $res['paid_amount']);
        $this->assertSame('paid', $res['status']);
    }

    public function test_backwards_and_terminal_status_transitions_are_rejected(): void
    {
        $auth = $this->admin();
        $wo = $this->makeWorkOrder($auth, 100);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'completed'], $auth)
            ->assertOk();
        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'assigned'], $auth)
            ->assertStatus(422);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 100], $auth)
            ->assertOk();
        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'in_progress'], $auth)
            ->assertStatus(422);

        // A paid work order is terminal, including for cancellation.
        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'cancelled'], $auth)
            ->assertStatus(422);
    }

    public function test_payment_on_cancelled_work_order_is_rejected(): void
    {
        $auth = $this->admin();
        $wo = $this->makeWorkOrder($auth, 100);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'cancelled'], $auth)
            ->assertOk();

        $this->postJson("/api/v1/work-orders/{$wo['id']}/payment", ['amount' => 50], $auth)
            ->assertStatus(422);

        $orders = collect($this->getJson("/api/v1/events/{$wo['event_id']}/work-orders", $auth)
            ->assertOk()->json('data'));
        $fresh = $orders->firstWhere('id', $wo['id']);
        $this->assertSame(0.0, (float) $fresh['paid_amount']);
        $this->assertSame('cancelled', $fresh['status']);
    }

    public function test_forward_and_cancel_transitions_still_work(): void
    {
        $auth = $this->admin();
        $wo = $this->makeWorkOrder($auth, 100);

        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'in_progress'], $auth)
            ->assertOk();
        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'in_progress'], $auth)
            ->assertOk(); // no-op is fine
        $this->postJson("/api/v1/work-orders/{$wo['id']}/status", ['status' => 'cancelled'], $auth)
            ->assertOk();
    }
}
