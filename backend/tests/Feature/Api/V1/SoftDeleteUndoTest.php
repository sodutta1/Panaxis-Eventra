<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\EventGuest;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SoftDeleteUndoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Sanctum::actingAs(User::where('email', 'admin@eventflow.test')->firstOrFail());
    }

    private function draftQuotation(): Quotation
    {
        $client = Client::firstOrFail();
        $id = $this->postJson('/api/v1/quotations', ['client_id' => $client->id, 'issue_date' => '2026-11-24', 'tax_rate' => 18,
            'items' => [['item_name' => 'Stage', 'quantity' => 1, 'unit_price' => 1000]]])->assertCreated()->json('data.id');

        return Quotation::findOrFail($id);
    }

    public function test_delete_hides_the_record_but_keeps_the_row_and_undo_brings_it_back(): void
    {
        $venue = Venue::firstOrFail();
        $this->deleteJson("/api/v1/venues/{$venue->id}")->assertOk();
        $this->assertSoftDeleted('venues', ['id' => $venue->id]);
        $this->getJson("/api/v1/venues/{$venue->id}")->assertNotFound();
        $this->assertNotContains($venue->id, collect($this->getJson('/api/v1/venues?per_page=100')->json('data'))->pluck('id')->all());

        $this->postJson("/api/v1/restore/venues/{$venue->id}")->assertOk();
        $this->assertNull(Venue::find($venue->id)->deleted_at);
        $this->getJson("/api/v1/venues/{$venue->id}")->assertOk();
    }

    public function test_lead_and_guest_are_undoable(): void
    {
        $lead = Lead::firstOrFail();
        $guest = EventGuest::firstOrFail();
        $this->deleteJson("/api/v1/leads/{$lead->id}")->assertOk();
        $this->deleteJson("/api/v1/guests/{$guest->id}")->assertOk();
        $this->postJson("/api/v1/restore/leads/{$lead->id}")->assertOk();
        $this->postJson("/api/v1/restore/guests/{$guest->id}")->assertOk();
        $this->assertNotNull(Lead::find($lead->id));
        $this->assertNotNull(EventGuest::find($guest->id));
    }

    public function test_only_draft_quotations_can_be_deleted_by_the_api(): void
    {
        $q = $this->draftQuotation();
        $q->update(['status' => 'sent']);
        $this->deleteJson("/api/v1/quotations/{$q->id}")->assertStatus(409);
        $this->assertNull($q->refresh()->deleted_at);
        foreach (['accepted', 'rejected', 'expired'] as $status) {
            $q->update(['status' => $status]);
            $this->deleteJson("/api/v1/quotations/{$q->id}")->assertStatus(409);
        }
        $q->update(['status' => 'draft']);
        $this->deleteJson("/api/v1/quotations/{$q->id}")->assertOk();
        $this->postJson("/api/v1/restore/quotations/{$q->id}")->assertOk();
        $this->assertNull($q->refresh()->deleted_at);
    }

    public function test_restore_is_admin_only_company_scoped_and_validates_input(): void
    {
        $venue = Venue::firstOrFail();
        $this->deleteJson("/api/v1/venues/{$venue->id}")->assertOk();
        Sanctum::actingAs(User::where('email', 'sales@eventflow.test')->firstOrFail());
        $this->postJson("/api/v1/restore/venues/{$venue->id}")->assertForbidden();
        Sanctum::actingAs(User::where('email', 'admin@eventflow.test')->firstOrFail());
        $this->postJson('/api/v1/restore/invoices/1')->assertNotFound();
        $this->postJson('/api/v1/restore/venues/abc')->assertNotFound();
        $this->postJson('/api/v1/restore/venues/99999999999999999999999')->assertNotFound();
        $this->postJson('/api/v1/restore/venues/987654')->assertNotFound();
        $this->postJson("/api/v1/restore/venues/{$venue->id}")->assertOk();
        $this->postJson("/api/v1/restore/venues/{$venue->id}")->assertNotFound();
    }

    public function test_money_records_are_still_never_deletable(): void
    {
        $invoice = \App\Models\Invoice::firstOrFail();
        $this->deleteJson("/api/v1/invoices/{$invoice->id}")->assertStatus(409);
        $this->assertNotNull(\App\Models\Invoice::find($invoice->id));
    }
}
