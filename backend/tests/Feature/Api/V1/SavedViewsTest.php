<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $email): User
    {
        $u = User::where('email', $email)->firstOrFail();
        Sanctum::actingAs($u);

        return $u;
    }

    public function test_requires_login(): void
    {
        $this->getJson('/api/v1/saved-views?module=/leads')->assertUnauthorized();
    }

    public function test_save_list_overwrite_and_delete(): void
    {
        $this->as('admin@eventflow.test');
        $this->postJson('/api/v1/saved-views', ['module' => '/leads', 'name' => 'Hot leads', 'query' => 'status=new&sort=name'])->assertCreated();
        $this->postJson('/api/v1/saved-views', ['module' => '/leads', 'name' => 'Hot leads', 'query' => 'status=won'])->assertCreated();
        $rows = $this->getJson('/api/v1/saved-views?module=/leads')->assertOk()->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('status=won', $rows[0]['query']);
        $this->assertSame([], $this->getJson('/api/v1/saved-views?module=/clients')->json('data'));
        $this->deleteJson('/api/v1/saved-views/'.$rows[0]['id'])->assertOk();
        $this->assertSame([], $this->getJson('/api/v1/saved-views?module=/leads')->json('data'));
    }

    public function test_views_belong_to_the_user_not_other_users(): void
    {
        $this->as('admin@eventflow.test');
        $id = $this->postJson('/api/v1/saved-views', ['module' => '/leads', 'name' => 'Mine', 'query' => 'a=1'])->json('data.id');
        $this->as('sales@eventflow.test');
        $this->assertSame([], $this->getJson('/api/v1/saved-views?module=/leads')->json('data'));
        $this->deleteJson("/api/v1/saved-views/{$id}")->assertNotFound();
        $this->as('admin@eventflow.test');
        $this->assertCount(1, $this->getJson('/api/v1/saved-views?module=/leads')->json('data'));
    }

    public function test_validation(): void
    {
        $this->as('admin@eventflow.test');
        $this->postJson('/api/v1/saved-views', ['module' => '/leads'])->assertStatus(422);
        $this->postJson('/api/v1/saved-views', ['module' => '/leads', 'name' => str_repeat('x', 81)])->assertStatus(422);
        $this->getJson('/api/v1/saved-views')->assertStatus(422);
        $this->deleteJson('/api/v1/saved-views/abc')->assertNotFound();
    }
}
