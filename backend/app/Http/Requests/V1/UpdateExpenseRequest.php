<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'nullable|exists:events,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'title' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:1',
            'incurred_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|string',
            'receipt_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ];
    }
}
