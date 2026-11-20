<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreInvoiceRequest;
use App\Http\Requests\V1\StorePaymentRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Invoice::with('client'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return InvoiceResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }
        $taxAmount = ($subtotal * $validated['tax_rate']) / 100;
        $total = $subtotal + $taxAmount;

        $invoice = Invoice::create([
            'company_id' => $this->companyId($request),
            'client_id' => $validated['client_id'],
            'event_id' => $validated['event_id'] ?? null,
            'invoice_number' => 'INV-' . date('Y') . '-' . rand(1000, 9999),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $taxAmount,
            'grand_total' => $total,
            'paid_amount' => 0,
            'balance_due' => $total,
            'status' => 'unpaid',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return (new InvoiceResource($invoice->load(['items', 'client'])))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $invoice)
    {
        return new InvoiceResource($this->findForCompany(Invoice::with(['items', 'payments', 'client']), $request, $invoice));
    }

    public function destroy(Request $request, int $invoice)
    {
        $this->findForCompany(Invoice::query(), $request, $invoice)->delete();

        return response()->json(['message' => 'Invoice deleted.']);
    }

    public function recordPayment(StorePaymentRequest $request, int $invoice)
    {
        $model = $this->findForCompany(Invoice::query(), $request, $invoice);
        $validated = $request->validated();

        $payment = Payment::create([
            'company_id' => $this->companyId($request),
            'invoice_id' => $model->id,
            'client_id' => $model->client_id,
            'event_id' => $model->event_id,
            'payment_number' => 'PAY-' . date('Y') . '-' . rand(1000, 9999),
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'status' => 'completed',
            'notes' => $validated['notes'] ?? null,
        ]);

        $newPaid = $model->paid_amount + $validated['amount'];
        $newBalance = $model->grand_total - $newPaid;
        $model->update([
            'paid_amount' => $newPaid,
            'balance_due' => $newBalance,
            'status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
        ]);

        if ($model->event) {
            $model->event->increment('amount_received', $validated['amount']);
        }

        return response()->json([
            'message' => 'Payment recorded.',
            'data' => new PaymentResource($payment),
            'invoice' => new InvoiceResource($model->fresh()),
        ], 201);
    }
}
