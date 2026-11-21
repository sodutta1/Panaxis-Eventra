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
        return ['status' => 'required|string|in:proposed,in_planning,confirmed,in_progress,completed,cancelled|max:255'];
    }
}
