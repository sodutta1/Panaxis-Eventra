<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'quotation_number' => $this->quotation_number,
            'client_id' => $this->client_id,
            'event_id' => $this->event_id,
            'issue_date' => $this->issue_date,
            'valid_until' => $this->valid_until,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items'),
            'client' => new ClientResource($this->whenLoaded('client')),
            'created_at' => $this->created_at,
        ];
    }
}
