<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OversizedRouteIdTest extends TestCase
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

    public function test_numeric_ids_beyond_int64_range_return_404_not_500(): void
    {
        $auth = $this->admin();

        // int64 max + 1 and a far larger value must not crash the int cast.
        $this->getJson('/api/v1/clients/9223372036854775808', $auth)->assertNotFound();
        $this->getJson('/api/v1/events/99999999999999999999', $auth)->assertNotFound();
        $this->getJson('/api/v1/invoices/99999999999999999999', $auth)->assertNotFound();
        $this->getJson('/api/v1/guests/99999999999999999999', $auth)->assertNotFound();
    }

    public function test_in_range_but_missing_ids_still_return_404(): void
    {
        $auth = $this->admin();

        $this->getJson('/api/v1/clients/9223372036854775807', $auth)->assertNotFound();
        $this->getJson('/api/v1/clients/424242', $auth)->assertNotFound();
    }
}
