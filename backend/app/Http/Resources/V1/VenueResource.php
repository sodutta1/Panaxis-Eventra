<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'rental_cost_per_day' => $this->rental_cost_per_day,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'city' => $this->city,
            'state' => $this->state,
            'address' => $this->address,
            'amenities' => $this->amenities,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
