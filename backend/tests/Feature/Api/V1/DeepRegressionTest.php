<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Company;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeepRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@eventflow.test')->firstOrFail());
    }

    private function foreignClient(): Client
    {
        $company = Company::create(['name' => 'Isolated tenant', 'code' => 'ISO']);

        return Client::create(['company_id' => $company->id, 'name' => 'Secret foreign client']);
    }

    public function test_foreign_client_cannot_be_attached_to_invoice(): void
    {
        $this->postJson('/api/v1/invoices', [
            'client_id' => $this->foreignClient()->id, 'invoice_date' => '2026-11-21', 'due_date' => '2026-12-21',
            'tax_rate' => 0, 'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_foreign_event_cannot_be_attached_to_guest(): void
    {
        $event = Event::firstOrFail()->replicate();
        $event->company_id = $this->foreignClient()->company_id;
        $event->event_code = 'ISOLATED';
        $event->save();
        $this->postJson('/api/v1/guests', ['event_id' => $event->id, 'name' => 'Probe', 'dietary_preference' => 'veg'])
            ->assertUnprocessable()->assertJsonValidationErrors('event_id');
    }

    public function test_cancelled_and_refunded_tickets_cannot_be_admitted(): void
    {
        $ticket = Ticket::firstOrFail();
        foreach (['cancelled', 'refunded'] as $status) {
            $ticket->update(['ticket_status' => $status]);
            $this->postJson('/api/v1/tickets/verify', ['token' => $ticket->qr_token])->assertStatus(422);
            $this->assertSame($status, $ticket->fresh()->ticket_status);
        }
    }

    public function test_ticket_type_must_belong_to_selected_event(): void
    {
        $type = TicketType::firstOrFail();
        $event = Event::where('company_id', $type->company_id)->where('id', '!=', $type->event_id)->firstOrFail();
        $this->postJson('/api/v1/tickets', ['event_id' => $event->id, 'ticket_type_id' => $type->id,
            'holder_name' => 'Probe', 'payment_status' => 'paid'])->assertUnprocessable();
    }

    public function test_ticket_capacity_is_enforced_atomically(): void
    {
        $type = TicketType::firstOrFail();
        $type->update(['total_capacity' => 1, 'sold_count' => 0]);
        $payload = ['event_id' => $type->event_id, 'ticket_type_id' => $type->id, 'holder_name' => 'Probe', 'payment_status' => 'paid'];
        $this->postJson('/api/v1/tickets', $payload)->assertCreated();
        $this->postJson('/api/v1/tickets', $payload)->assertUnprocessable();
        $this->assertSame(1, $type->fresh()->sold_count);
    }

    public function test_ticket_cancel_releases_capacity_and_event_count(): void
    {
        $type = TicketType::firstOrFail();
        $before = $type->sold_count;
        $eventBefore = $type->event->confirmed_guests;
        $id = $this->postJson('/api/v1/tickets', ['event_id' => $type->event_id, 'ticket_type_id' => $type->id,
            'holder_name' => 'Probe', 'payment_status' => 'paid'])->assertCreated()->json('data.0.id');
        $this->deleteJson('/api/v1/tickets/'.$id)->assertStatus(409);
        $this->patchJson('/api/v1/tickets/'.$id,['ticket_status'=>'cancelled'])->assertOk();
        $this->assertSame($before, $type->fresh()->sold_count);
        $this->assertSame($eventBefore, $type->event->fresh()->confirmed_guests);
    }

    public function test_inactive_user_cannot_log_in_or_use_existing_token(): void
    {
        $user = User::where('email', 'admin@eventflow.test')->firstOrFail();
        $user->update(['status' => 'inactive']);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(422);
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/v1/events')->assertForbidden();
    }

    public function test_unassigned_user_never_defaults_to_first_company(): void
    {
        $user = User::where('email', 'admin@eventflow.test')->firstOrFail();
        $user->update(['company_id' => null]);
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/v1/events')->assertForbidden();
    }

    public function test_negative_tax_and_due_date_before_invoice_are_rejected(): void
    {
        $payload = ['client_id' => Client::firstOrFail()->id, 'invoice_date' => '2026-11-21', 'due_date' => '2026-11-20',
            'tax_rate' => -200, 'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 10]]];
        $this->postJson('/api/v1/invoices', $payload)->assertUnprocessable()->assertJsonValidationErrors(['tax_rate', 'due_date']);
    }

    public function test_huge_numeric_and_overlong_strings_return_validation_not_500(): void
    {
        $this->postJson('/api/v1/venues', ['name' => 'Huge', 'type' => 'hotel', 'city' => str_repeat('x', 256),
            'capacity' => 2147483648, 'rental_cost_per_day' => 1e20])->assertUnprocessable();
    }

    public function test_nullable_ticket_price_is_rejected_on_patch(): void
    {
        $ticket = Ticket::firstOrFail();
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['price_paid' => null])->assertUnprocessable();
    }

    public function test_invalid_missing_and_empty_scan_tokens_do_not_mutate_tickets(): void
    {
        foreach ([[], ['token' => ''], ['token' => []]] as $payload) {
            $this->postJson('/api/v1/tickets/verify', $payload)->assertUnprocessable();
        }
        $this->postJson('/api/v1/tickets/verify', ['token' => 'not-a-ticket'])->assertNotFound();
    }
}
