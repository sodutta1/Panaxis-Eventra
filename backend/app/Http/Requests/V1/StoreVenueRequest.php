<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'rental_cost_per_day' => 'required|numeric|min:0',
            'contact_person' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'address' => 'nullable|string',
            'amenities' => 'nullable|string',
        ];
    }
}
