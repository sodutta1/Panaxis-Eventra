<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreTicketRequest;
use App\Http\Requests\V1\StoreTicketTypeRequest;
use App\Http\Requests\V1\VerifyTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Ticket::with(['event', 'ticketType']), $request);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('ticket_status')) {
            $query->where('ticket_status', $request->ticket_status);
        }

        return TicketResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreTicketRequest $request)
    {
        $validated = $request->validated();
        $companyId = $this->companyId($request);

        $event = $this->findForCompany(Event::query(), $request, (int) $validated['event_id']);
        $ticketType = $this->scopeCompany(TicketType::query(), $request)->findOrFail($validated['ticket_type_id']);

        $qty = $validated['quantity'] ?? 1;
        $unitPrice = $validated['price_paid'] ?? $ticketType->price;
        $createdTickets = [];

        for ($i = 0; $i < $qty; $i++) {
            $createdTickets[] = Ticket::create([
                'company_id' => $companyId,
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'ticket_number' => 'TCK-' . date('Y') . '-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999),
                'qr_token' => 'QR-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999),
                'holder_name' => $validated['holder_name'] . ($qty > 1 ? ' (#' . ($i + 1) . ')' : ''),
                'holder_email' => $validated['holder_email'] ?? null,
                'holder_phone' => $validated['holder_phone'] ?? null,
                'price_paid' => $unitPrice,
                'payment_status' => $validated['payment_status'],
                'ticket_status' => 'valid',
            ]);
        }

        $ticketType->increment('sold_count', $qty);
        $event->increment('confirmed_guests', $qty);

        return TicketResource::collection(collect($createdTickets))->response()->setStatusCode(201);
    }

    public function storeType(StoreTicketTypeRequest $request)
    {
        $type = TicketType::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'sold_count' => 0,
            'is_active' => true,
        ]));

        return response()->json(['data' => $type], 201);
    }

    /**
     * Gate scan: same behavior as the web scanner.
     * POST /api/v1/tickets/verify
     */
    public function verifyTicket(VerifyTicketRequest $request)
    {
        $token = trim($request->token);
        $ticket = Ticket::with(['event.venue', 'ticketType'])
            ->where('company_id', $this->companyId($request))
            ->where(function ($q) use ($token) {
                $q->where('qr_token', $token)->orWhere('ticket_number', $token);
            })
            ->first();

        $scannerName = $request->user()->name ?? 'Gate Scanner Operator';

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'INVALID TICKET: Scanned token "' . $token . '" is not recognized.',
            ], 404);
        }

        if ($ticket->ticket_status === 'checked_in') {
            return response()->json([
                'status' => 'warning',
                'message' => "ALREADY ADMITTED! Ticket #{$ticket->ticket_number} ({$ticket->holder_name} - {$ticket->ticketType->name}) was checked in on " . ($ticket->checked_in_at ? Carbon::parse($ticket->checked_in_at)->format('M d, Y h:i A') : 'earlier') . ' by ' . ($ticket->checked_in_by ?? 'Gate Staff') . '.',
                'ticket' => new TicketResource($ticket),
            ], 409);
        }

        $ticket->update([
            'ticket_status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => $scannerName,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "VALID PASS! {$ticket->holder_name} admitted to {$ticket->event->title} ({$ticket->ticketType->name}).",
            'ticket' => new TicketResource($ticket->fresh(['event', 'ticketType'])),
        ]);
    }

    public function show(Request $request, int $ticket)
    {
        return new TicketResource($this->findForCompany(Ticket::query(), $request, $ticket));
    }
}
