<?php

namespace App\Http\Requests\V1;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // The {invoice} route parameter may be a bound model or a raw id
        // depending on where validation runs; support both.
        $routeInvoice = $this->route('invoice');
        $invoice = $routeInvoice instanceof Invoice ? $routeInvoice : Invoice::find($routeInvoice);

        return [
            'amount' => 'required|numeric|min:1|max:' . ($invoice ? $invoice->balance_due : 0),
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'transaction_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
