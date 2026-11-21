<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestGroup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InputAndConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@eventflow.test')->firstOrFail());
    }

    private function invoicePayload(): array
    {
        return ['client_id' => Client::firstOrFail()->id, 'invoice_date' => '2026-11-21', 'due_date' => '2026-12-21',
            'tax_rate' => 18, 'items' => [['description' => 'Café ₹ & <script> literal text', 'quantity' => 1, 'unit_price' => 0.03]]];
    }

    public function test_document_rounding_matches_persisted_line_items(): void
    {
        $payload = $this->invoicePayload();
        $payload['items'][0]['quantity'] = 3;
        $data = $this->postJson('/api/v1/invoices', $payload)->assertCreated()->json('data');
        $this->assertEquals(0.09, (float) $data['subtotal']);
        $this->assertEquals(0.02, (float) $data['tax_amount']);
        $this->assertEquals(0.11, (float) $data['grand_total']);
        $this->assertEquals(0.09, (float) $data['items'][0]['total_price']);
    }

    public function test_document_overflow_is_rejected_before_partial_writes(): void
    {
        $payload = $this->invoicePayload();
        $payload['items'][0]['quantity'] = 99999999;
        $payload['items'][0]['unit_price'] = 9999999999;
        $before = Invoice::count();
        $this->postJson('/api/v1/invoices', $payload)->assertUnprocessable();
        $this->assertSame($before, Invoice::count());
    }

    public function test_fractional_money_beyond_two_places_is_rejected(): void
    {
        $payload = $this->invoicePayload();
        $payload['items'][0]['unit_price'] = 0.335;
        $this->postJson('/api/v1/invoices', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_duplicate_lead_conversion_returns_the_same_client(): void
    {
        $lead = Lead::where('status', '!=', 'converted')->firstOrFail();
        $before = Client::count();
        $first = $this->postJson('/api/v1/leads/'.$lead->id.'/convert')->assertOk()->json('client_id');
        $second = $this->postJson('/api/v1/leads/'.$lead->id.'/convert')->assertOk()->json('client_id');
        $this->assertSame($first, $second);
        $this->assertSame($before + 1, Client::count());
    }

    public function test_event_date_only_patches_use_the_persisted_other_boundary(): void
    {
        $event = Event::firstOrFail();
        $event->update(['start_date' => '2026-12-01', 'end_date' => '2026-12-03']);
        $this->patchJson('/api/v1/events/'.$event->id, ['end_date' => '2026-12-02'])->assertOk();
        $this->patchJson('/api/v1/events/'.$event->id, ['start_date' => '2026-12-04'])->assertUnprocessable();
        $this->patchJson('/api/v1/events/'.$event->id, ['end_date' => '2026-11-30'])->assertUnprocessable();
    }

    public function test_nullable_nonnullable_columns_and_array_ids_do_not_cause_server_errors(): void
    {
        $event = Event::firstOrFail();
        $this->patchJson('/api/v1/events/'.$event->id, ['estimated_budget' => null])->assertUnprocessable();
        $this->postJson('/api/v1/guests', ['name' => 'Boundary', 'event_id' => [$event->id], 'dietary_preference' => 'veg'])->assertUnprocessable();
        $this->postJson('/api/v1/guests', ['name' => 'Boundary', 'event_id' => $event->id, 'dietary_preference' => 'veg', 'plus_ones' => null])->assertUnprocessable();
    }

    public function test_login_rejects_array_password_and_accepts_nullable_device_name(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'admin@eventflow.test', 'password' => ['password']])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => 'admin@eventflow.test', 'password' => 'password', 'device_name' => null])->assertOk();
    }

    public function test_schedule_times_are_validated(): void
    {
        $event = Event::firstOrFail();
        foreach ([['bad'], '99:99', 'nonsense'] as $time) {
            $this->postJson('/api/v1/events/'.$event->id.'/schedules', ['title' => 'Boundary', 'start_time' => $time])->assertUnprocessable();
        }
        $this->postJson('/api/v1/events/'.$event->id.'/schedules', ['title' => 'Boundary', 'start_time' => '09:15'])->assertCreated();
        $this->postJson('/api/v1/events/'.$event->id.'/schedules', ['title' => 'Boundary', 'start_time' => '09:15:30'])->assertCreated();
    }

    public function test_empty_create_payloads_return_422_for_all_new_modules(): void
    {
        foreach (['venues', 'vendors', 'quotations', 'expenses', 'guests', 'tickets', 'tickets/types', 'invoices', 'clients', 'events', 'leads'] as $path) {
            $this->postJson('/api/v1/'.$path, [])->assertUnprocessable();
        }
    }

    public function test_max_length_unicode_ticket_holder_and_bulk_creation_are_safe(): void
    {
        $type = TicketType::firstOrFail();
        $this->postJson('/api/v1/tickets', ['event_id' => $type->event_id, 'ticket_type_id' => $type->id,
            'holder_name' => str_repeat('é', 255), 'quantity' => 2, 'payment_status' => 'paid'])->assertCreated();
    }

    public function test_unicode_pdf_and_long_multipage_invoice_generate_without_partial_html(): void
    {
        $payload = $this->invoicePayload();
        $payload['notes'] = str_repeat('Plain escaped text: <script>alert(1)</script> Café ₹. ', 20);
        $payload['items'] = array_fill(0, 65, ['description' => str_repeat('Long unbroken description ', 6), 'quantity' => 1, 'unit_price' => 10]);
        $payload['items'][0]['description'] = str_repeat('W', 255);
        $id = $this->postJson('/api/v1/invoices', $payload)->assertCreated()->json('data.id');
        $pdf = $this->get('/api/v1/invoices/'.$id.'/pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        file_put_contents('/tmp/eventra-edge-invoice.pdf', $pdf->getContent());
    }

    public function test_long_quotation_pdf_generates_with_bounded_columns(): void
    {
        $payload = ['client_id' => Client::firstOrFail()->id, 'issue_date' => '2026-11-21', 'tax_rate' => 18,
            'items' => array_fill(0, 35, ['item_name' => 'Café ₹ service with a readable description', 'quantity' => 1, 'unit_price' => 10])];
        $payload['items'][0]['item_name'] = str_repeat('W', 255);
        $id = $this->postJson('/api/v1/quotations', $payload)->assertCreated()->json('data.id');
        $pdf = $this->get('/api/v1/quotations/'.$id.'/pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        file_put_contents('/tmp/eventra-edge-quotation.pdf', $pdf->getContent());
    }

    public function test_budget_overflow_rolls_back_the_new_item(): void
    {
        $event = Event::firstOrFail();
        $path = '/api/v1/events/'.$event->id.'/budget/items';
        $event->budget?->items()->delete();
        $this->postJson($path, ['category' => 'Boundary', 'item_name' => 'Limit', 'estimated_cost' => 9999999999.99])->assertCreated();
        $this->postJson($path, ['category' => 'Boundary', 'item_name' => 'Overflow', 'estimated_cost' => 1])->assertUnprocessable();
        $budget = $this->getJson('/api/v1/events/'.$event->id.'/budget')->assertOk()->json('data');
        $this->assertCount(1, $budget['items']);
        $this->assertEquals(9999999999.99, (float) $budget['total_estimated_cost']);
    }

    public function test_admin_rejects_passwords_beyond_bcrypt_byte_limit(): void
    {
        foreach ([str_repeat('x', 73), str_repeat('é', 40)] as $password) {
            $this->postJson('/api/v1/admin/users', ['name' => 'Boundary', 'email' => 'boundary@eventflow.test',
                'role' => 'staff', 'password' => $password])->assertUnprocessable()->assertJsonValidationErrors('password');
        }
    }

    public function test_small_invoice_balances_can_be_paid_in_full(): void
    {
        $payload = $this->invoicePayload();
        $id = $this->postJson('/api/v1/invoices', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/invoices/'.$id.'/payments', ['amount' => 0.04, 'payment_date' => '2026-11-21', 'payment_method' => 'upi'])
            ->assertCreated()->assertJsonPath('invoice.status', 'paid');
    }

    public function test_invoice_client_must_match_its_event(): void
    {
        $event = Event::whereNotNull('client_id')->firstOrFail();
        $client = Client::where('id', '!=', $event->client_id)->firstOrFail();
        $payload = $this->invoicePayload();
        $payload['event_id'] = $event->id;
        $payload['client_id'] = $client->id;
        $this->postJson('/api/v1/invoices', $payload)->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_guest_cannot_keep_a_group_from_another_event_after_moving(): void
    {
        $group = GuestGroup::firstOrFail();
        $guest = EventGuest::where('event_id', $group->event_id)->firstOrFail();
        $guest->update(['guest_group_id' => $group->id]);
        $other = Event::where('id', '!=', $group->event_id)->firstOrFail();
        $this->patchJson('/api/v1/guests/'.$guest->id, ['event_id' => $other->id])->assertUnprocessable();
        $this->patchJson('/api/v1/guests/'.$guest->id, ['event_id' => $other->id, 'guest_group_id' => null])->assertOk();
    }

    public function test_event_delete_has_no_orphan_tickets_or_types(): void
    {
        $event = Event::has('tickets')->firstOrFail();
        $this->deleteJson('/api/v1/events/'.$event->id)->assertOk();
        $this->assertDatabaseMissing('tickets', ['event_id' => $event->id]);
        $this->assertDatabaseMissing('ticket_types', ['event_id' => $event->id]);
        $this->assertDatabaseMissing('event_days', ['event_id' => $event->id]);
    }
}
