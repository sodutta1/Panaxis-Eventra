<?php

namespace App\Http\Requests\V1;

class StoreScheduleRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_day_id' => 'nullable|integer|min:1|exists:event_days,id',
            'schedule_date' => 'nullable|date',
            'start_time' => 'required|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s|after:start_time',
            'title' => 'required|string|max:255',
            'location_within_venue' => 'nullable|string|max:255',
            'speaker_performer' => 'nullable|string|max:255',
            'activity_description' => 'nullable|string|max:16000',
            'in_charge_person' => 'nullable|string|max:255',
        ];
    }
}
