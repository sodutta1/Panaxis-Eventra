<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string',
            'capacity' => 'sometimes|required|integer|min:1',
            'rental_cost_per_day' => 'sometimes|required|numeric|min:0',
            'contact_person' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'city' => 'sometimes|required|string',
            'state' => 'nullable|string',
            'address' => 'nullable|string',
            'amenities' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ];
    }
}
