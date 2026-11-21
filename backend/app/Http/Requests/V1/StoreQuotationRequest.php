<?php

namespace App\Http\Requests\V1;

class StoreQuotationRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|integer|min:1|exists:clients,id',
            'event_id' => 'nullable|integer|min:1|exists:events,id',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'tax_rate' => 'required|numeric|decimal:0,2|min:0|max:100',
            'notes' => 'nullable|string|max:16000',
            'items' => 'required|array|min:1|max:500',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|decimal:0,2|min:1|max:99999999.99',
            'items.*.unit_price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
        ];
    }
}
