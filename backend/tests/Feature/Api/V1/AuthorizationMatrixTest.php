<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventGuest;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_nine_roles_are_denied_every_route_outside_their_declared_role_sets(): void
    {
        $roles = ['super_admin', 'company_admin', 'admin', 'event_manager', 'sales_executive', 'accountant', 'staff', 'client', 'vendor'];
        $user = User::where('email', 'admin@eventflow.test')->firstOrFail();
        $probes = 0;
        foreach ($roles as $role) {
            $user->update(['role' => $role]);
            Sanctum::actingAs($user->fresh());
            foreach (Route::getRoutes() as $route) {
                if (! str_starts_with($route->uri(), 'api/v1/')) {
                    continue;
                }
                $sets = array_filter($route->gatherMiddleware(), fn ($m) => str_starts_with($m, 'role:'));
                if (! $sets) {
                    continue;
                }
                $denied = false;
                foreach ($sets as $set) {
                    if (! in_array($role, explode(',', substr($set, 5)), true)) {
                        $denied = true;
                    }
                }
                if (! $denied) {
                    continue;
                }
                $url = '/'.preg_replace('/\{[^}]+\}/', '999999', $route->uri());
                $this->json($route->methods()[0], $url, [])->assertForbidden();
                $probes++;
            }
        }
        $this->assertGreaterThan(250, $probes);
        file_put_contents('/tmp/eventra-role-probes.txt', (string) $probes);
    }

    public function test_foreign_resource_ids_cannot_be_read_patched_or_deleted(): void
    {
        $company = Company::create(['name' => 'Other', 'code' => 'OTHER']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Other admin', 'email' => 'other@isolation.test',
            'password' => bcrypt('not-a-production-password'), 'role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($user);
        $resources = ['clients' => Client::class, 'events' => Event::class, 'tickets' => Ticket::class,
            'invoices' => Invoice::class, 'leads' => Lead::class, 'guests' => EventGuest::class,
            'expenses' => EventExpense::class, 'vendors' => Vendor::class, 'venues' => Venue::class,
            'quotations' => Quotation::class];
        foreach ($resources as $resource => $model) {
            $id = $model::firstOrFail()->id;
            $this->getJson('/api/v1/'.$resource.'/'.$id)->assertNotFound();
            $this->deleteJson('/api/v1/'.$resource.'/'.$id)->assertNotFound();
            if (! in_array($resource, ['invoices', 'quotations'])) {
                $this->patchJson('/api/v1/'.$resource.'/'.$id, [])->assertNotFound();
            }
        }
        $ticket = Ticket::firstOrFail();
        $this->postJson('/api/v1/tickets/verify', ['token' => $ticket->qr_token])->assertNotFound();
        foreach (['invoices' => Invoice::firstOrFail()->id, 'quotations' => Quotation::firstOrFail()->id] as $type => $id) {
            $this->getJson('/api/v1/'.$type.'/'.$id.'/pdf')->assertNotFound();
        }
    }

    public function test_all_tenant_reference_fields_reject_foreign_ids_on_writes(): void
    {
        $company = Company::create(['name' => 'Foreign references', 'code' => 'FOREIGN-REF']);
        Sanctum::actingAs(User::where('email', 'admin@eventflow.test')->firstOrFail());
        $event = Event::firstOrFail();
        $cases = [
            ['events', $event->id, 'client_id', 'clients'],
            ['events', $event->id, 'event_category_id', 'event_categories'],
            ['events', $event->id, 'venue_id', 'venues'],
            ['events', $event->id, 'manager_id', 'users'],
            ['vendors', Vendor::firstOrFail()->id, 'vendor_category_id', 'vendor_categories'],
            ['expenses', EventExpense::firstOrFail()->id, 'event_id', 'events'],
            ['expenses', EventExpense::firstOrFail()->id, 'expense_category_id', 'expense_categories'],
            ['expenses', EventExpense::firstOrFail()->id, 'vendor_id', 'vendors'],
            ['guests', EventGuest::firstOrFail()->id, 'event_id', 'events'],
            ['guests', EventGuest::firstOrFail()->id, 'guest_group_id', 'guest_groups'],
            ['leads', Lead::firstOrFail()->id, 'assigned_to', 'users'],
        ];
        foreach ($cases as [$resource, $id, $field, $table]) {
            $row = (array) DB::table($table)->first();
            unset($row['id']);
            $row['company_id'] = $company->id;
            if (isset($row['email']) && $table === 'users') {
                $row['email'] = uniqid('foreign-').'@example.test';
            }
            if (isset($row['event_code'])) {
                $row['event_code'] = uniqid('FOREIGN-');
            }
            $foreignId = DB::table($table)->insertGetId($row);
            $this->patchJson('/api/v1/'.$resource.'/'.$id, [$field => $foreignId])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_legacy_erp_is_disabled_and_vendor_login_does_not_receive_arbitrary_vendor(): void
    {
        $this->get('/login')->assertNotFound();
        $user = User::where('email', 'admin@eventflow.test')->firstOrFail();
        $this->actingAs($user)->get('/events')->assertNotFound();
        $vendor = User::where('role', 'vendor')->firstOrFail();
        Sanctum::actingAs($vendor);
        $this->getJson('/api/v1/portal/vendor')->assertForbidden()->assertJsonMissingPath('data');
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/portal/vendor')->assertOk();
    }

    public function test_production_cannot_enable_unsafe_legacy_erp(): void
    {
        $this->app['env'] = 'production';
        config(['app.legacy_erp' => true]);
        $this->get('/login')->assertNotFound();
    }
}
