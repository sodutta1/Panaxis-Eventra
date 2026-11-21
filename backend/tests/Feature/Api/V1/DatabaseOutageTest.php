<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseOutageTest extends TestCase
{
    // No RefreshDatabase: this test deliberately breaks the connection.

    public function test_connection_level_failure_returns_503_not_opaque_500(): void
    {
        // Point the connection at a dead port, simulating a MySQL outage.
        config(['database.connections.mysql.port' => 3399]);
        DB::purge('mysql');

        try {
            $this->getJson('/api/v1/events', ['Authorization' => 'Bearer fake-token'])
                ->assertStatus(503)
                ->assertJsonPath('message', 'Service temporarily unavailable.');
        } finally {
            config(['database.connections.mysql.port' => 3307]);
            DB::purge('mysql');
        }
    }
}
