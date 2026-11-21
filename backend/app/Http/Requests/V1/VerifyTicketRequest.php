<?php

namespace App\Http\Requests\V1;

class VerifyTicketRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['token' => 'required|string|max:255'];
    }
}
