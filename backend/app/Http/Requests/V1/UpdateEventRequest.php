<?php

namespace App\Http\Requests\V1;

use App\Models\Event;

class UpdateEventRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $event = Event::where('company_id', $this->user()->company_id)->find($this->route('event'));
        $start = $this->has('start_date') ? 'start_date' : $event?->start_date?->format('Y-m-d H:i:s');
        $end = $this->has('end_date') ? 'end_date' : $event?->end_date?->format('Y-m-d H:i:s');

        return [
            'title' => 'sometimes|required|string|max:255',
            'client_id' => 'sometimes|required|integer|min:1|exists:clients,id',
            'event_category_id' => 'sometimes|required|integer|min:1|exists:event_categories,id',
            'event_type' => 'sometimes|required|string|max:255',
            'venue_id' => 'nullable|integer|min:1|exists:venues,id',
            'manager_id' => 'nullable|integer|min:1|exists:users,id',
            'start_date' => 'sometimes|required|date'.($end ? '|before_or_equal:'.$end : ''),
            'end_date' => 'sometimes|required|date'.($start ? '|after_or_equal:'.$start : ''),
            'expected_guests' => 'sometimes|required|integer|min:0|max:2147483647',
            'estimated_budget' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'contract_value' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'status' => 'sometimes|required|string|max:255',
            'theme_concept' => 'nullable|string|max:16000',
            'description' => 'nullable|string|max:16000',
        ];
    }
}
