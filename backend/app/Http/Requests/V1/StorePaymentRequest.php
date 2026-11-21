<?php

namespace App\Http\Requests\V1;

use App\Models\Invoice;

class StorePaymentRequest extends TenantRequest
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
        $invoice = $routeInvoice instanceof Invoice ? $routeInvoice : Invoice::where('company_id', $this->user()->company_id)->find($routeInvoice);

        return [
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:'.($invoice ? $invoice->balance_due : 0),
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:16000',
        ];
    }
}
