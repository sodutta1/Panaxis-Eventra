<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'client_id' => 'sometimes|required|exists:clients,id',
            'event_category_id' => 'sometimes|required|exists:event_categories,id',
            'event_type' => 'sometimes|required|string',
            'venue_id' => 'nullable|exists:venues,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'expected_guests' => 'nullable|integer|min:0',
            'estimated_budget' => 'nullable|numeric|min:0',
            'contract_value' => 'nullable|numeric|min:0',
            'status' => 'sometimes|required|string',
            'theme_concept' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }
}
