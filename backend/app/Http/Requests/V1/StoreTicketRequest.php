<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'holder_name' => 'required|string|max:255',
            'holder_email' => 'nullable|email|max:255',
            'holder_phone' => 'nullable|string|max:25',
            'quantity' => 'nullable|integer|min:1|max:20',
            'price_paid' => 'nullable|numeric|min:0',
            'payment_status' => 'required|string|in:paid,complimentary,refunded,pending',
        ];
    }
}
