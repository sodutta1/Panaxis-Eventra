<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'event_id' => $this->event_id,
            'ticket_type_id' => $this->ticket_type_id,
            'ticket_number' => $this->ticket_number,
            'qr_token' => $this->qr_token,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'holder_phone' => $this->holder_phone,
            'price_paid' => $this->price_paid,
            'payment_status' => $this->payment_status,
            'ticket_status' => $this->ticket_status,
            'checked_in_at' => $this->checked_in_at,
            'checked_in_by' => $this->checked_in_by,
            'event' => new EventResource($this->whenLoaded('event')),
            'ticket_type' => $this->whenLoaded('ticketType'),
            'created_at' => $this->created_at,
        ];
    }
}
