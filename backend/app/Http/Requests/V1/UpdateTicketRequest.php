<?php

namespace App\Http\Requests\V1;

class UpdateTicketRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holder_name' => 'sometimes|required|string|max:255',
            'holder_email' => 'nullable|email|max:255',
            'holder_phone' => 'nullable|string|max:25',
            'price_paid' => 'sometimes|required|numeric|decimal:0,2|min:0|max:99999999.99',
            'payment_status' => 'sometimes|required|string|in:paid,complimentary,refunded,pending|max:255',
            'ticket_status' => 'sometimes|required|string|in:valid,checked_in,cancelled,refunded|max:255',
        ];
    }
}
