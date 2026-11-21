<?php

namespace App\Http\Requests\V1;

class StoreVendorRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'vendor_category_id' => 'required|integer|min:1|exists:vendor_categories,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'services_offered' => 'nullable|string|max:16000',
            'payment_terms' => 'nullable|string|max:255',
            'bank_details' => 'nullable|string|max:16000',
        ];
    }
}
