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
use App\Notifications\TicketPurchaseConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Ticket::with(['event', 'ticketType']), $request);
        foreach (['event_id', 'ticket_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $this->listResponse($query, $request, TicketResource::class);
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

        foreach ($tickets as $ticket) {
            $this->sendPurchaseConfirmation($ticket);
        }

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
            if ($ticket->event->status === 'completed') {
                return response()->json(['status' => 'error', 'message' => 'INVALID TICKET: This event has already completed.'], 422);
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

    /**
     * Notify the buyer after a sale. Notification failures must never break
     * a completed sale, so they are rescued and logged instead.
     */
    protected function sendPurchaseConfirmation(Ticket $ticket): void
    {
        if (! $ticket->holder_email && ! $ticket->holder_phone) {
            return;
        }

        rescue(function () use ($ticket) {
            $routes = [];
            if ($ticket->holder_email) {
                $routes['mail'] = $ticket->holder_email;
            }
            if ($ticket->holder_phone) {
                $routes['whatsapp'] = $ticket->holder_phone;
            }
            \Illuminate\Support\Facades\Notification::routes($routes)
                ->notify(new TicketPurchaseConfirmation($ticket->loadMissing(['event.venue', 'ticketType'])));
        }, report: true);
    }

    public function types(Request $request)
    {
        return response()->json(['data' => $this->scopeCompany(TicketType::query(), $request)->orderBy('name')->get()]);
    }

    public function update(UpdateTicketRequest $request,int $ticket)
    {
        $candidate=$this->findForCompany(Ticket::query(),$request,$ticket);
        return DB::transaction(function() use($request,$candidate){
            $event=$this->findForCompany(Event::query()->lockForUpdate(),$request,$candidate->event_id);
            $type=$this->findForCompany(TicketType::query()->lockForUpdate(),$request,$candidate->ticket_type_id);
            $model=$this->findForCompany(Ticket::query()->lockForUpdate(),$request,$candidate->id);
            $data=$request->validated();
            $inactive=fn($status)=>in_array($status,['cancelled','refunded']);
            $wasInactive=$inactive($model->ticket_status);$willBeInactive=$inactive($data['ticket_status']??$model->ticket_status);
            if($wasInactive&&!$willBeInactive){
                abort_if(in_array($event->status,['completed','cancelled']),422,'Tickets cannot be restored for a closed event.');
                abort_if(!$type->is_active||$type->sold_count >= $type->total_capacity,422,'This ticket type has no available capacity.');
                $type->increment('sold_count');$event->increment('confirmed_guests');
            } elseif(!$wasInactive&&$willBeInactive){
                $type->update(['sold_count'=>max(0,$type->sold_count-1)]);$event->update(['confirmed_guests'=>max(0,$event->confirmed_guests-1)]);
            }
            if(isset($data['ticket_status'])){
                $data['checked_in_at']=$data['ticket_status']==='checked_in'?($model->checked_in_at??now()):null;
                $data['checked_in_by']=$data['ticket_status']==='checked_in'?($model->checked_in_by??$request->user()->name):null;
            }
            $model->update($data);return new TicketResource($model->fresh(['event','ticketType']));
        },3);
    }

    public function destroy(Request $request,int $ticket){
        $this->findForCompany(Ticket::query(),$request,$ticket);
        return response()->json(['message'=>'Ticket sales records cannot be deleted. Cancel the ticket instead.'],409);
    }


}
