<?php

namespace App\Http\Requests\V1;

class StoreTicketRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|min:1|exists:events,id',
            'ticket_type_id' => 'required|integer|min:1|exists:ticket_types,id',
            'holder_name' => 'required|string|max:255',
            'holder_email' => 'nullable|email|max:255',
            'holder_phone' => 'nullable|string|max:25',
            'quantity' => 'nullable|integer|min:1|max:20',
            'price_paid' => 'nullable|numeric|decimal:0,2|min:0|max:99999999.99',
            'payment_status' => 'required|string|in:paid,complimentary,refunded,pending|max:255',
        ];
    }
}
