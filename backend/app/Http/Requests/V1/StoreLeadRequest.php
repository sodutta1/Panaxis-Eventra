<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC enforced by the role middleware
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'required|string',
            'event_type' => 'nullable|string',
            'event_date' => 'nullable|date',
            'estimated_guests' => 'nullable|integer',
            'estimated_budget' => 'nullable|numeric',
            'assigned_to' => 'nullable|exists:users,id',
            'requirements' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
