<?php

namespace App\Http\Requests\V1;

class UpdateVenueRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1|max:2147483647',
            'rental_cost_per_day' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'city' => 'sometimes|required|string|max:255',
            'state' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:16000',
            'amenities' => 'nullable|string|max:255',
            'status' => 'sometimes|required|string|max:255',
        ];
    }
}
