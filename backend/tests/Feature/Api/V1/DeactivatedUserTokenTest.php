<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedUserTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tokenFor(string $email): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertOk()->json('token');
    }

    public function test_tokens_issued_before_deactivation_are_revoked(): void
    {
        $sales = User::where('email', 'sales@eventflow.test')->firstOrFail();
        $salesToken = $this->tokenFor('sales@eventflow.test');
        $adminToken = $this->tokenFor('admin@eventflow.test');

        $this->patchJson("/api/v1/admin/users/{$sales->id}", ['status' => 'inactive'], [
            'Authorization' => 'Bearer ' . $adminToken,
        ])->assertOk()->assertJsonPath('data.status', 'inactive');

        // Reset guard state so the next request resolves only from the bearer
        // token, like a real fresh HTTP request would.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer ' . $salesToken])
            ->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/clients', ['Authorization' => 'Bearer ' . $salesToken])
            ->assertStatus(401);
    }

    public function test_token_stops_working_when_status_changes_outside_admin_api(): void
    {
        $sales = User::where('email', 'sales@eventflow.test')->firstOrFail();
        $salesToken = $this->tokenFor('sales@eventflow.test');

        $sales->update(['status' => 'inactive']);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer ' . $salesToken])
            ->assertForbidden();
    }

    public function test_active_user_token_still_works(): void
    {
        $token = $this->tokenFor('sales@eventflow.test');

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('data.email', 'sales@eventflow.test');
    }
}
