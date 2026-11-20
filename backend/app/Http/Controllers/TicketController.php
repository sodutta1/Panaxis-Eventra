<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['event.venue', 'ticketType']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('ticket_status')) {
            $query->where('ticket_status', $request->ticket_status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('qr_token', 'like', "%{$s}%")
                  ->orWhere('holder_name', 'like', "%{$s}%")
                  ->orWhere('holder_email', 'like', "%{$s}%")
                  ->orWhere('holder_phone', 'like', "%{$s}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);
        $allEvents = Event::with('ticketTypes')->orderBy('start_date', 'desc')->get();
        $ticketTypes = TicketType::with('event')->where('is_active', true)->get();

        return view('tickets.index', compact('tickets', 'allEvents', 'ticketTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'holder_name' => 'required|string|max:255',
            'holder_email' => 'nullable|email|max:255',
            'holder_phone' => 'nullable|string|max:25',
            'quantity' => 'nullable|integer|min:1|max:20',
            'price_paid' => 'nullable|numeric|min:0',
            'payment_status' => 'required|string|in:paid,complimentary,refunded,pending',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $event = Event::findOrFail($validated['event_id']);
        $ticketType = TicketType::findOrFail($validated['ticket_type_id']);
        $qty = $validated['quantity'] ?? 1;
        $unitPrice = $validated['price_paid'] ?? $ticketType->price;

        $createdTickets = [];

        for ($i = 0; $i < $qty; $i++) {
            $ticketNumber = 'TCK-' . date('Y') . '-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999);
            $qrToken = 'QR-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999);

            $ticket = Ticket::create([
                'company_id' => $companyId,
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'ticket_number' => $ticketNumber,
                'qr_token' => $qrToken,
                'holder_name' => $validated['holder_name'] . ($qty > 1 ? " (#" . ($i + 1) . ")" : ""),
                'holder_email' => $validated['holder_email'],
                'holder_phone' => $validated['holder_phone'],
                'price_paid' => $unitPrice,
                'payment_status' => $validated['payment_status'],
                'ticket_status' => 'valid',
            ]);

            $createdTickets[] = $ticket;
        }

        $ticketType->increment('sold_count', $qty);
        $event->increment('confirmed_guests', $qty);

        return redirect()->route('tickets.index')->with('success', "Generated {$qty} ticket(s) for {$validated['holder_name']} under {$ticketType->name}.");
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'total_capacity' => 'required|integer|min:1',
            'benefits' => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()->company_id ?? 1;

        TicketType::create(array_merge($validated, [
            'company_id' => $companyId,
            'sold_count' => 0,
            'is_active' => true,
        ]));

        return back()->with('success', "New Ticket Tier '{$validated['name']}' created successfully.");
    }

    public function verifyTicket(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $token = trim($request->token);
        $ticket = Ticket::with(['event.venue', 'ticketType'])
            ->where('qr_token', $token)
            ->orWhere('ticket_number', $token)
            ->first();

        $scannerName = auth()->user()->name ?? 'Gate Scanner Operator';

        if (!$ticket) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'INVALID TICKET: Scanned token "' . $token . '" is not recognized.',
                ], 404);
            }
            return back()->with('error', 'INVALID TICKET: Scanned token "' . $token . '" is not recognized.');
        }

        if ($ticket->ticket_status === 'checked_in') {
            $msg = "ALREADY ADMITTED! Ticket #{$ticket->ticket_number} ({$ticket->holder_name} - {$ticket->ticketType->name}) was checked in on " . ($ticket->checked_in_at ? $ticket->checked_in_at->format('M d, Y h:i A') : 'earlier') . " by " . ($ticket->checked_in_by ?? 'Gate Staff') . ".";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => $msg,
                    'ticket' => $ticket,
                ], 409);
            }
            return back()->with('warning', $msg);
        }

        $ticket->update([
            'ticket_status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => $scannerName,
        ]);

        $successMsg = "VALID PASS! {$ticket->holder_name} admitted to {$ticket->event->title} ({$ticket->ticketType->name}).";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'ticket' => $ticket,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function print(Ticket $ticket)
    {
        $ticket->load(['event.venue', 'ticketType', 'event.client']);
        return view('tickets.print', compact('ticket'));
    }
}
