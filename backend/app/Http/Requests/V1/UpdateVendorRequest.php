<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'vendor_category_id' => 'sometimes|required|exists:vendor_categories,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'services_offered' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'bank_details' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ];
    }
}
