<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
            'price_paid' => 'nullable|numeric|min:0',
            'payment_status' => 'sometimes|required|string|in:paid,complimentary,refunded,pending',
            'ticket_status' => 'sometimes|required|string|in:valid,checked_in,cancelled,refunded',
        ];
    }
}
