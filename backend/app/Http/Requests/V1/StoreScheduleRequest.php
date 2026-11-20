<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_day_id' => 'nullable|exists:event_days,id',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'title' => 'required|string|max:255',
            'location_within_venue' => 'nullable|string|max:255',
            'speaker_performer' => 'nullable|string|max:255',
            'activity_description' => 'nullable|string',
            'in_charge_person' => 'nullable|string|max:255',
        ];
    }
}
