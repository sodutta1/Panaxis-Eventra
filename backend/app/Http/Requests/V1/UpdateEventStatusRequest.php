<?php

namespace App\Http\Requests\V1;

class UpdateEventStatusRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'required|string|max:255'];
    }
}
