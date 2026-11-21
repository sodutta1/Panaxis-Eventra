<?php

namespace Tests\Feature\Api\V1;

use App\Models\BookingOrder;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventCategory;
use App\Notifications\TicketPurchaseConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicBookingTest extends TestCase
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
            'email' => 'admin@eventflow.test', 'password' => 'password',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function bookableEvent(string $status = 'confirmed'): array
    {
        $auth = $this->admin();
        $event = Event::findOrFail($this->postJson('/api/v1/events', [
            'title' => 'Public Booking Fest',
            'client_id' => Client::firstOrFail()->id,
            'event_category_id' => EventCategory::firstOrFail()->id,
            'event_type' => 'concert',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => $status,
        ], $auth)->assertCreated()->json('data.id'));

        $typeId = $this->postJson('/api/v1/tickets/types', [
            'event_id' => $event->id, 'name' => 'General', 'price' => 250, 'total_capacity' => 3,
        ], $auth)->assertCreated()->json('data.id');

        return [$event, $typeId];
    }

    public function test_public_event_page_shows_event_types_and_branding(): void
    {
        [$event, $typeId] = $this->bookableEvent();

        $res = $this->getJson('/api/v1/public/events/'.$event->id)->assertOk()->json('data');

        $this->assertSame('Public Booking Fest', $res['title']);
        $this->assertTrue($res['bookable']);
        $this->assertArrayHasKey('name', $res['company']);
        $this->assertTrue($res['payment']['test_mode']);
        $this->assertSame('General', $res['ticket_types'][0]['name']);
        $this->assertSame(3, $res['ticket_types'][0]['remaining']);
    }

    public function test_full_booking_flow_in_test_mode(): void
    {
        Notification::fake();
        [$event, $typeId] = $this->bookableEvent();

        $order = $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId,
            'quantity' => 2,
            'holder_name' => 'Demo Buyer',
            'holder_email' => 'buyer@example.com',
            'holder_phone' => '+919000000099',
        ])->assertCreated()->json('data');

        $this->assertSame(500.0, (float) $order['amount']);
        $this->assertStringStartsWith('order_test_', $order['razorpay_order_id']);

        $payment = $this->postJson('/api/v1/public/bookings/'.$order['booking_id'].'/simulate-payment')
            ->assertOk()->json('data');

        $res = $this->postJson('/api/v1/public/bookings/'.$order['booking_id'].'/confirm', $payment)
            ->assertOk()->json('data');

        $this->assertSame('paid', $res['status']);
        $this->assertCount(2, $res['tickets']);
        $this->assertStringContainsString('/api/v1/public/tickets/', $res['tickets'][0]['eticket_url']);
        $this->assertSame(2, (int) $event->fresh()->confirmed_guests);

        Notification::assertSentOnDemandTimes(TicketPurchaseConfirmation::class, 2);

        // Idempotent retry
        $this->postJson('/api/v1/public/bookings/'.$order['booking_id'].'/confirm', $payment)->assertOk();
        $this->assertSame(2, BookingOrder::findOrFail($order['booking_id'])->tickets()->count());
    }

    public function test_bad_signature_is_rejected(): void
    {
        [$event, $typeId] = $this->bookableEvent();
        $order = $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId, 'quantity' => 1,
            'holder_name' => 'Fraud', 'holder_email' => 'fraud@example.com',
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/public/bookings/'.$order['booking_id'].'/confirm', [
            'razorpay_payment_id' => 'pay_test_fake', 'razorpay_signature' => 'bogus',
        ])->assertStatus(422);

        $this->assertSame('created', BookingOrder::findOrFail($order['booking_id'])->status);
        $this->assertSame(0, $event->tickets()->count());
    }

    public function test_capacity_is_reserved_and_expired_holds_release(): void
    {
        [$event, $typeId] = $this->bookableEvent();

        $order = $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId, 'quantity' => 3,
            'holder_name' => 'Hogger', 'holder_email' => 'hog@example.com',
        ])->assertCreated()->json('data');

        // Capacity exhausted while the hold is live
        $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId, 'quantity' => 1,
            'holder_name' => 'Next', 'holder_email' => 'next@example.com',
        ])->assertStatus(422);

        // Expire the hold; capacity frees up on the next booking attempt
        BookingOrder::findOrFail($order['booking_id'])->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId, 'quantity' => 1,
            'holder_name' => 'Next', 'holder_email' => 'next@example.com',
        ])->assertCreated();

        $this->assertSame('expired', BookingOrder::findOrFail($order['booking_id'])->status);
    }

    public function test_completed_event_is_not_bookable_publicly(): void
    {
        [$event, $typeId] = $this->bookableEvent('completed');

        $res = $this->getJson('/api/v1/public/events/'.$event->id)->assertOk()->json('data');
        $this->assertFalse($res['bookable']);

        $this->postJson('/api/v1/public/events/'.$event->id.'/book', [
            'ticket_type_id' => $typeId, 'quantity' => 1,
            'holder_name' => 'Late', 'holder_email' => 'late@example.com',
        ])->assertStatus(422);
    }
}
