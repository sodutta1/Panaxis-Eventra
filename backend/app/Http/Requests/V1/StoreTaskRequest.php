<?php

namespace App\Http\Requests\V1;

class StoreTaskRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|integer|min:1|exists:users,id',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'priority' => 'required|string|max:255',
            'description' => 'nullable|string|max:16000',
        ];
    }
}
