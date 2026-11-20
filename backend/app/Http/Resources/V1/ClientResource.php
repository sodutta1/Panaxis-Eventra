<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'client_code' => $this->client_code,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'tax_number' => $this->tax_number,
            'client_type' => $this->client_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'events_count' => $this->whenCounted('events'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
