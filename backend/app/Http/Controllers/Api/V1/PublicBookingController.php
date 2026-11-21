<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BookingOrder;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Notifications\TicketPurchaseConfirmation;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Public, unauthenticated ticket booking. Events are addressable by their
 * event_code; ticket capacity is reserved when the order is created and
 * released again if the order expires unpaid.
 */
class PublicBookingController extends Controller
{
    private const BOOKABLE_STATUSES = ['confirmed', 'in_progress'];

    public function show(int $event)
    {
        $event = Event::with(['venue', 'company', 'ticketTypes' => fn ($q) => $q->where('is_active', true)])
            ->findOrFail($event);

        $bookable = in_array($event->status, self::BOOKABLE_STATUSES, true);

        return response()->json(['data' => [
            'id' => $event->id,
            'title' => $event->title,
            'event_code' => $event->event_code,
            'event_type' => $event->event_type,
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'venue' => $event->venue ? ['name' => $event->venue->name, 'city' => $event->venue->city ?? null] : null,
            'bookable' => $bookable,
            'status' => $event->status,
            'company' => [
                'name' => $event->company->name,
                'logo_url' => $event->company->logo_url,
            ],
            'ticket_types' => $event->ticketTypes->map(fn (TicketType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'price' => $t->price,
                'benefits' => $t->benefits,
                'remaining' => max(0, $t->total_capacity - $t->sold_count),
            ])->values(),
            'currency_symbol' => $event->company->currency_symbol ?? '₹',
            'payment' => [
                'provider' => 'razorpay',
                'test_mode' => app(RazorpayService::class)->isTestMode(),
                'key' => app(RazorpayService::class)->publicKey(),
            ],
        ]]);
    }

    public function book(Request $request, int $event, RazorpayService $razorpay)
    {
        $data = $request->validate([
            'ticket_type_id' => 'required|integer|min:1|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10',
            'holder_name' => 'required|string|max:255',
            'holder_email' => 'required|email|max:255',
            'holder_phone' => 'nullable|string|max:25',
        ]);

        $order = DB::transaction(function () use ($data, $event, $razorpay) {
            $this->releaseExpiredHolds();

            $eventModel = Event::query()->lockForUpdate()->findOrFail($event);
            if (! in_array($eventModel->status, self::BOOKABLE_STATUSES, true)) {
                throw ValidationException::withMessages(['event' => 'This event is not open for booking.']);
            }

            $type = TicketType::query()->lockForUpdate()
                ->where('event_id', $eventModel->id)
                ->findOrFail($data['ticket_type_id']);
            $qty = (int) $data['quantity'];
            $now = now();
            if (! $type->is_active
                || ($type->sales_start && $now->lt($type->sales_start))
                || ($type->sales_end && $now->gt($type->sales_end))
                || $type->sold_count + $qty > $type->total_capacity) {
                throw ValidationException::withMessages(['ticket_type_id' => 'This ticket type is sold out or unavailable.']);
            }

            $type->increment('sold_count', $qty); // reserve capacity

            $amount = $type->price * $qty;
            $rpOrder = $razorpay->createOrder($amount, $eventModel->company->currency ?? 'INR', 'bk_'.Str::lower(Str::random(10)));

            return BookingOrder::create([
                'company_id' => $eventModel->company_id,
                'event_id' => $eventModel->id,
                'ticket_type_id' => $type->id,
                'quantity' => $qty,
                'holder_name' => $data['holder_name'],
                'holder_email' => $data['holder_email'],
                'holder_phone' => $data['holder_phone'] ?? null,
                'amount' => $amount,
                'currency' => $eventModel->company->currency ?? 'INR',
                'razorpay_order_id' => $rpOrder['id'],
                'status' => 'created',
                'expires_at' => now()->addMinutes(30),
            ]);
        }, 3);

        return response()->json(['data' => [
            'booking_id' => $order->id,
            'razorpay_order_id' => $order->razorpay_order_id,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'test_mode' => $razorpay->isTestMode(),
            'key' => $razorpay->publicKey(),
            'expires_at' => $order->expires_at,
        ]], 201);
    }

    /**
     * Test-mode stand-in for Razorpay checkout: returns a payment id and a
     * genuine HMAC signature for the order. Absent in a live integration,
     * where Razorpay's checkout returns these after the buyer pays.
     */
    public function simulatePayment(int $booking, RazorpayService $razorpay)
    {
        $order = BookingOrder::where('status', 'created')->findOrFail($booking);
        abort_if($order->expires_at->isPast(), 410, 'This booking has expired.');

        return response()->json(['data' => $razorpay->simulatePayment($order->razorpay_order_id)]);
    }

    public function confirm(Request $request, int $booking, RazorpayService $razorpay)
    {
        $data = $request->validate([
            'razorpay_payment_id' => 'required|string|max:64',
            'razorpay_signature' => 'required|string|max:128',
        ]);

        $result = DB::transaction(function () use ($data, $booking, $razorpay) {
            $order = BookingOrder::query()->lockForUpdate()->findOrFail($booking);

            if ($order->status === 'paid') {
                return $order; // idempotent retry
            }
            abort_if($order->status !== 'created', 422, 'This booking can no longer be paid.');
            abort_if($order->expires_at->isPast(), 410, 'This booking has expired.');

            if (! $razorpay->verifySignature($order->razorpay_order_id, $data['razorpay_payment_id'], $data['razorpay_signature'])) {
                throw ValidationException::withMessages(['razorpay_signature' => 'Payment verification failed.']);
            }

            $type = TicketType::query()->findOrFail($order->ticket_type_id);
            $event = Event::query()->lockForUpdate()->findOrFail($order->event_id);

            for ($i = 0; $i < $order->quantity; $i++) {
                $suffix = $order->quantity > 1 ? ' (#'.($i + 1).')' : '';
                Ticket::create([
                    'company_id' => $order->company_id,
                    'event_id' => $order->event_id,
                    'ticket_type_id' => $order->ticket_type_id,
                    'booking_order_id' => $order->id,
                    'ticket_number' => 'TCK-'.date('Y').'-'.Str::upper((string) Str::ulid()),
                    'qr_token' => 'QR-'.Str::random(40),
                    'holder_name' => $order->holder_name.$suffix,
                    'holder_email' => $order->holder_email,
                    'holder_phone' => $order->holder_phone,
                    'price_paid' => $type->price,
                    'payment_status' => 'paid',
                    'ticket_status' => 'valid',
                ]);
            }
            $event->increment('confirmed_guests', $order->quantity);

            $order->forceFill([
                'status' => 'paid',
                'razorpay_payment_id' => $data['razorpay_payment_id'],
            ])->save();

            return $order;
        }, 3);

        $tickets = $result->tickets()->get();
        foreach ($tickets as $ticket) {
            rescue(function () use ($ticket) {
                $routes = ['mail' => $ticket->holder_email];
                if ($ticket->holder_phone) {
                    $routes['whatsapp'] = $ticket->holder_phone;
                }
                Notification::routes($routes)->notify(
                    new TicketPurchaseConfirmation($ticket->loadMissing(['event.venue', 'ticketType']))
                );
            }, report: true);
        }

        return response()->json(['data' => [
            'booking_id' => $result->id,
            'status' => $result->status,
            'tickets' => $tickets->map(fn (Ticket $t) => [
                'ticket_number' => $t->ticket_number,
                'holder_name' => $t->holder_name,
                'eticket_url' => url('/api/v1/public/tickets/'.$t->qr_token.'/eticket'),
            ])->values(),
        ]]);
    }

    /** Release capacity held by orders that expired unpaid. */
    private function releaseExpiredHolds(): void
    {
        BookingOrder::where('status', 'created')->where('expires_at', '<', now())
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        $locked = BookingOrder::query()->lockForUpdate()->find($order->id);
                        if (! $locked || $locked->status !== 'created') {
                            return;
                        }
                        TicketType::where('id', $locked->ticket_type_id)
                            ->where('sold_count', '>=', $locked->quantity)
                            ->decrement('sold_count', $locked->quantity);
                        $locked->forceFill(['status' => 'expired'])->save();
                    }, 3);
                }
            });
    }
}
