<?php

namespace App\Http\Requests\V1;

class StoreEventRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'client_id' => 'required|integer|min:1|exists:clients,id',
            'event_category_id' => 'required|integer|min:1|exists:event_categories,id',
            'event_type' => 'required|string|max:255',
            'venue_id' => 'nullable|integer|min:1|exists:venues,id',
            'manager_id' => 'nullable|integer|min:1|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'expected_guests' => 'sometimes|required|integer|min:0|max:2147483647',
            'estimated_budget' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'contract_value' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'status' => 'required|string|in:proposed,in_planning,confirmed,in_progress,completed,cancelled|max:255',
            'theme_concept' => 'nullable|string|max:16000',
            'description' => 'nullable|string|max:16000',
        ];
    }
}
