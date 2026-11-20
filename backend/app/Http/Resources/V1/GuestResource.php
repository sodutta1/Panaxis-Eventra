<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'event_id' => $this->event_id,
            'guest_group_id' => $this->guest_group_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'plus_ones' => $this->plus_ones,
            'table_number' => $this->table_number,
            'dietary_preference' => $this->dietary_preference,
            'rsvp_status' => $this->rsvp_status,
            'checked_in' => $this->checked_in,
            'checked_in_at' => $this->checked_in_at,
            'created_at' => $this->created_at,
        ];
    }
}
