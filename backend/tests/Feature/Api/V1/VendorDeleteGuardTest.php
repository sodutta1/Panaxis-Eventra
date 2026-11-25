<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\EventCategory;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDeleteGuardTest extends TestCase
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

    private function makeVendor(array $auth, string $name): Vendor
    {
        $id = $this->postJson('/api/v1/vendors', [
            'name' => $name, 'vendor_category_id' => VendorCategory::firstOrFail()->id,
        ], $auth)->assertCreated()->json('data.id');

        return Vendor::findOrFail($id);
    }

    private function makeWorkOrder(array $auth, int $vendorId, float $contract): int
    {
        $eventId = $this->postJson('/api/v1/events', [
            'title' => 'Vendor Guard Event',
            'client_id' => Client::firstOrFail()->id,
            'event_type' => 'corporate',
            'event_category_id' => EventCategory::firstOrFail()->id,
            'venue_id' => Venue::firstOrFail()->id,
            'start_date' => '2027-05-10',
            'end_date' => '2027-05-11',
            'expected_guests' => 100,
            'estimated_budget' => 10000,
            'contract_value' => 20000,
            'status' => 'confirmed',
        ], $auth)->assertCreated()->json('data.id');

        return $this->postJson("/api/v1/events/{$eventId}/work-orders", [
            'vendor_id' => $vendorId, 'service_title' => 'Guard WO', 'contract_amount' => $contract,
        ], $auth)->assertCreated()->json('data.id');
    }

    public function test_vendor_with_recorded_payments_cannot_be_deleted(): void
    {
        $auth = $this->admin();
        $vendor = $this->makeVendor($auth, 'Paid Guard Vendor');
        $woId = $this->makeWorkOrder($auth, $vendor->id, 1000);
        $this->postJson("/api/v1/work-orders/{$woId}/payment", ['amount' => 400], $auth)->assertOk();

        $this->deleteJson("/api/v1/vendors/{$vendor->id}", [], $auth)->assertStatus(409);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
        $this->assertDatabaseHas('vendor_assignments', ['id' => $woId, 'paid_amount' => 400]);
    }

    public function test_vendor_with_only_unpaid_work_orders_can_be_deleted(): void
    {
        $auth = $this->admin();
        $vendor = $this->makeVendor($auth, 'Unpaid Guard Vendor');
        $this->makeWorkOrder($auth, $vendor->id, 1000);

        $this->deleteJson("/api/v1/vendors/{$vendor->id}", [], $auth)->assertOk();

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    public function test_vendor_without_work_orders_can_be_deleted(): void
    {
        $auth = $this->admin();
        $vendor = $this->makeVendor($auth, 'Clean Guard Vendor');

        $this->deleteJson("/api/v1/vendors/{$vendor->id}", [], $auth)->assertOk();

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }
}
