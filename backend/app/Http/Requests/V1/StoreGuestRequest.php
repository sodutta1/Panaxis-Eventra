<?php

namespace App\Http\Requests\V1;

class StoreGuestRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|min:1|exists:events,id',
            'guest_group_id' => 'nullable|integer|min:1|exists:guest_groups,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'plus_ones' => 'sometimes|required|integer|min:0|max:2147483647',
            'table_number' => 'nullable|string|max:255',
            'dietary_preference' => 'required|string|max:255',
        ];
    }
}
