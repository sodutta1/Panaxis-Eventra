<?php

namespace App\Http\Requests\V1;

class UpdateClientRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'alternate_phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:16000',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'client_type' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string|max:16000',
        ];
    }
}
