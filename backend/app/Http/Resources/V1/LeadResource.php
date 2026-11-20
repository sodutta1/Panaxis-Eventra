<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'event_type' => $this->event_type,
            'event_date' => $this->event_date,
            'estimated_guests' => $this->estimated_guests,
            'estimated_budget' => $this->estimated_budget,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'client_id' => $this->client_id,
            'requirements' => $this->requirements,
            'notes' => $this->notes,
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
