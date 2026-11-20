<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'contact_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'sometimes|required|string',
            'event_type' => 'nullable|string',
            'event_date' => 'nullable|date',
            'estimated_guests' => 'nullable|integer',
            'estimated_budget' => 'nullable|numeric',
            'assigned_to' => 'nullable|exists:users,id',
            'requirements' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ];
    }
}
