<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'sometimes|required|exists:events,id',
            'guest_group_id' => 'nullable|exists:guest_groups,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'plus_ones' => 'nullable|integer|min:0',
            'table_number' => 'nullable|string',
            'dietary_preference' => 'sometimes|required|string',
            'rsvp_status' => 'sometimes|required|string|in:pending,confirmed,declined',
        ];
    }
}
