<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use App\Models\Client;
use App\Models\EventCategory;
use App\Models\Venue;
use App\Models\Lead;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\TicketType;
use Carbon\Carbon;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function getAdmin()
    {
        return User::where('role', 'super_admin')->first();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('EventFlow');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Operations');
    }

    public function test_events_directory_renders(): void
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get('/events');
        $response->assertStatus(200);
        $response->assertSee('Event Operations Directory');
    }

    public function test_event_details_and_run_of_show_renders(): void
    {
        $admin = $this->getAdmin();
        $event = Event::first();
        $response = $this->actingAs($admin)->get('/events/' . $event->id);
        $response->assertStatus(200);
        $response->assertSee($event->event_code);
    }

    public function test_event_status_transition(): void
    {
        $admin = $this->getAdmin();
        $event = Event::first();
        $response = $this->actingAs($admin)->post('/events/' . $event->id . '/status', [
            'status' => 'active'
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('active', $event->fresh()->status);
    }

    public function test_lead_conversion_workflow(): void
    {
        $admin = $this->getAdmin();
        $lead = Lead::create([
            'company_id' => $admin->company_id ?? 1,
            'title' => 'Test Inbound Event Lead',
            'contact_name' => 'Kunal Kapoor',
            'email' => 'kunal@kapoor.test',
            'phone' => '+91 99887 76655',
            'source' => 'website',
            'status' => 'qualified',
            'estimated_budget' => 500000,
        ]);

        $response = $this->actingAs($admin)->post('/leads/' . $lead->id . '/convert');
        $response->assertRedirect(route('events.create'));
        $this->assertEquals('converted', $lead->fresh()->status);
    }

    public function test_ticket_generation_and_print_flow(): void
    {
        $admin = $this->getAdmin();
        $ticketType = TicketType::first();
        $event = $ticketType->event;

        $response = $this->actingAs($admin)->post('/tickets', [
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'holder_name' => 'Sophia Martinez',
            'holder_email' => 'sophia@test.com',
            'holder_phone' => '+1 555 444 3333',
            'quantity' => 1,
            'payment_status' => 'paid',
        ]);

        $response->assertSessionHas('success');
        $ticket = Ticket::where('holder_name', 'Sophia Martinez')->first();
        $this->assertNotNull($ticket);

        $printResponse = $this->actingAs($admin)->get('/tickets/' . $ticket->id . '/print');
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Sophia Martinez');

        $verifyResponse = $this->actingAs($admin)->post('/tickets/verify', [
            'token' => $ticket->qr_token,
        ]);
        $verifyResponse->assertSessionHas('success');
        $this->assertEquals('checked_in', $ticket->fresh()->ticket_status);
    }
}
