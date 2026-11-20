<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'event_code' => $this->event_code,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'status' => $this->status,
            'client_id' => $this->client_id,
            'event_category_id' => $this->event_category_id,
            'venue_id' => $this->venue_id,
            'manager_id' => $this->manager_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'expected_guests' => $this->expected_guests,
            'confirmed_guests' => $this->confirmed_guests,
            'estimated_budget' => $this->estimated_budget,
            'contract_value' => $this->contract_value,
            'amount_received' => $this->amount_received,
            'theme_concept' => $this->theme_concept,
            'description' => $this->description,
            'client' => new ClientResource($this->whenLoaded('client')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'category' => $this->whenLoaded('category'),
            'manager' => new UserResource($this->whenLoaded('manager')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
