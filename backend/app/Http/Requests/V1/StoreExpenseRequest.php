<?php

namespace App\Http\Requests\V1;

class StoreExpenseRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'nullable|integer|min:1|exists:events,id',
            'expense_category_id' => 'nullable|integer|min:1|exists:expense_categories,id',
            'vendor_id' => 'nullable|integer|min:1|exists:vendors,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|decimal:0,2|min:1|max:9999999999.99',
            'incurred_date' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:16000',
        ];
    }
}
