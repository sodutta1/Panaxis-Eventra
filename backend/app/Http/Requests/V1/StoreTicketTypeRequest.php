<?php

namespace App\Http\Requests\V1;

class StoreTicketTypeRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|min:1|exists:events,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'total_capacity' => 'required|integer|min:1|max:2147483647',
            'benefits' => 'nullable|string|max:1000',
        ];
    }
}
