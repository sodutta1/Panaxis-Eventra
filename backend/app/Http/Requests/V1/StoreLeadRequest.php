<?php

namespace App\Http\Requests\V1;

class StoreLeadRequest extends TenantRequest
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
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'source' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
            'estimated_guests' => 'sometimes|required|integer|max:2147483647|min:0',
            'estimated_budget' => 'sometimes|required|numeric|decimal:0,2|max:9999999999.99|min:0',
            'assigned_to' => 'nullable|integer|min:1|exists:users,id',
            'requirements' => 'nullable|string|max:16000',
            'notes' => 'nullable|string|max:16000',
        ];
    }
}
