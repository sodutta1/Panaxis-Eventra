<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'invoice_number' => $this->invoice_number,
            'client_id' => $this->client_id,
            'event_id' => $this->event_id,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items'),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'created_at' => $this->created_at,
        ];
    }
}
