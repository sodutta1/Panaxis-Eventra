<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'event_day_id' => $this->event_day_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'title' => $this->title,
            'location_within_venue' => $this->location_within_venue,
            'speaker_performer' => $this->speaker_performer,
            'activity_description' => $this->activity_description,
            'in_charge_person' => $this->in_charge_person,
        ];
    }
}
