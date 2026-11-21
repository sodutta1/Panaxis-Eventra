<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreTicketRequest;
use App\Http\Requests\V1\StoreTicketTypeRequest;
use App\Http\Requests\V1\UpdateTicketRequest;
use App\Http\Requests\V1\VerifyTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Ticket::with(['event', 'ticketType']), $request);
        foreach (['event_id', 'ticket_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return TicketResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();
        $tickets = DB::transaction(function () use ($request, $data) {
            // Lock the event before the type in every ticket mutation to avoid lock inversion.
            $event = $this->findForCompany(Event::query()->lockForUpdate(), $request, (int) $data['event_id']);
            if (in_array($event->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages(['event_id' => 'Cannot sell tickets for a cancelled or completed event.']);
            }
            $type = $this->findForCompany(TicketType::query()->lockForUpdate(), $request, (int) $data['ticket_type_id']);
            $qty = $data['quantity'] ?? 1;
            if ($type->event_id !== $event->id || ! $type->is_active || $type->sold_count + $qty > $type->total_capacity) {
                throw ValidationException::withMessages(['ticket_type_id' => 'This ticket type is unavailable or has insufficient capacity.']);
            }
            $tickets = collect();
            for ($i = 0; $i < $qty; $i++) {
                $suffix = $qty > 1 ? ' (#'.($i + 1).')' : '';
                if (mb_strlen($data['holder_name'].$suffix) > 255) {
                    $suffix = '';
                }
                $tickets->push(Ticket::create([
                    'company_id' => $event->company_id, 'event_id' => $event->id, 'ticket_type_id' => $type->id,
                    'ticket_number' => 'TCK-'.date('Y').'-'.Str::upper((string) Str::ulid()),
                    'qr_token' => 'QR-'.Str::random(40), 'holder_name' => $data['holder_name'].$suffix,
                    'holder_email' => $data['holder_email'] ?? null, 'holder_phone' => $data['holder_phone'] ?? null,
                    'price_paid' => $data['price_paid'] ?? $type->price, 'payment_status' => $data['payment_status'],
                    'ticket_status' => 'valid',
                ]));
            }
            $type->increment('sold_count', $qty);
            $event->increment('confirmed_guests', $qty);

            return $tickets;
        }, 3);

        return TicketResource::collection($tickets)->response()->setStatusCode(201);
    }

    public function storeType(StoreTicketTypeRequest $request)
    {
        $type = TicketType::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request), 'sold_count' => 0, 'is_active' => true,
        ]));

        return response()->json(['data' => $type], 201);
    }

    public function verifyTicket(VerifyTicketRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $token = trim($request->token);
            $ticket = Ticket::with(['event.venue', 'ticketType'])
                ->where('company_id', $this->companyId($request))
                ->where(fn ($q) => $q->where('qr_token', $token)->orWhere('ticket_number', $token))
                ->lockForUpdate()->first();
            if (! $ticket) {
                return response()->json(['status' => 'error', 'message' => 'INVALID TICKET: Token is not recognized.'], 404);
            }
            if ($ticket->ticket_status === 'checked_in') {
                return response()->json(['status' => 'warning', 'message' => 'ALREADY ADMITTED! Ticket #'.$ticket->ticket_number,
                    'ticket' => new TicketResource($ticket)], 409);
            }
            if ($ticket->ticket_status !== 'valid' || $ticket->payment_status === 'refunded') {
                return response()->json(['status' => 'error', 'message' => 'INVALID TICKET: This pass has been cancelled or refunded.'], 422);
            }
            $ticket->update(['ticket_status' => 'checked_in', 'checked_in_at' => now(), 'checked_in_by' => $request->user()->name]);

            return response()->json(['status' => 'success',
                'message' => 'VALID PASS! '.$ticket->holder_name.' admitted to '.$ticket->event->title.' ('.$ticket->ticketType->name.').',
                'ticket' => new TicketResource($ticket)], 200);
        }, 3);
    }

    public function show(Request $request, int $ticket)
    {
        return new TicketResource($this->findForCompany(Ticket::query(), $request, $ticket));
    }

    public function types(Request $request)
    {
        return response()->json(['data' => $this->scopeCompany(TicketType::query(), $request)->orderBy('name')->get()]);
    }

    public function update(UpdateTicketRequest $request, int $ticket)
    {
        $model = $this->findForCompany(Ticket::query(), $request, $ticket);
        $data = $request->validated();
        if (isset($data['ticket_status'])) {
            if ($data['ticket_status'] === 'checked_in') {
                $data['checked_in_at'] = $model->checked_in_at ?? now();
                $data['checked_in_by'] = $model->checked_in_by ?? $request->user()->name;
            } else {
                $data['checked_in_at'] = null;
                $data['checked_in_by'] = null;
            }
        }
        $model->update($data);

        return new TicketResource($model->fresh(['event', 'ticketType']));
    }

    public function destroy(Request $request, int $ticket)
    {
        $model = $this->findForCompany(Ticket::query(), $request, $ticket);
        DB::transaction(function () use ($request, $model) {
            $event = $this->findForCompany(Event::query()->lockForUpdate(), $request, $model->event_id);
            $type = $this->findForCompany(TicketType::query()->lockForUpdate(), $request, $model->ticket_type_id);
            $locked = $this->findForCompany(Ticket::query()->lockForUpdate(), $request, $model->id);
            $locked->delete();
            if ($type->sold_count > 0) {
                $type->decrement('sold_count');
            }
            if ($event->confirmed_guests > 0) {
                $event->decrement('confirmed_guests');
            }
        }, 3);

        return response()->json(['message' => 'Ticket deleted.']);
    }
}
