<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreInvoiceRequest;
use App\Http\Requests\V1\StorePaymentRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Invoice::with('client'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->listResponse($query, $request, InvoiceResource::class);
    }

    public function store(StoreInvoiceRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = round($subtotal + round($item['quantity'] * $item['unit_price'], 2), 2);
            }
            $taxAmount = round(($subtotal * $validated['tax_rate']) / 100, 2);
            $total = round($subtotal + $taxAmount, 2);

            if ($total > 9999999999.99) {
                throw ValidationException::withMessages(['items' => 'Document total exceeds the supported amount.']);
            }

            $invoice = Invoice::create([
                'company_id' => $this->companyId($request),
                'client_id' => $validated['client_id'],
                'event_id' => $validated['event_id'] ?? null,
                'invoice_number' => 'INV-'.date('Y').'-'.Str::upper((string) Str::ulid()),
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
                    'total_price' => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            return (new InvoiceResource($invoice->load(['items', 'client'])))
                ->response()->setStatusCode(201);
        }, 3);
    }

    public function show(Request $request, int $invoice)
    {
        return new InvoiceResource($this->findForCompany(Invoice::with(['items', 'payments', 'client']), $request, $invoice));
    }

    public function destroy(Request $request, int $invoice)
    {
        $this->findForCompany(Invoice::query(),$request,$invoice);
        return response()->json(['message'=>'Invoices are retained as financial records.'],409);
    }

    public function recordPayment(StorePaymentRequest $request, int $invoice)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $invoice, $validated) {
            $model = $this->findForCompany(Invoice::query()->lockForUpdate(), $request, $invoice);
            // Validation ran before the lock. Recheck the current balance under it.
            if ((float) $validated['amount'] > (float) $model->balance_due) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the current balance.']);
            }
            $payment = Payment::create([
                'company_id' => $this->companyId($request), 'invoice_id' => $model->id,
                'client_id' => $model->client_id, 'event_id' => $model->event_id,
                'payment_number' => 'PAY-'.date('Y').'-'.Str::upper((string) Str::ulid()),
                'payment_date' => $validated['payment_date'], 'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'status' => 'completed', 'notes' => $validated['notes'] ?? null,
            ]);
            $paid = round((float) $model->paid_amount + (float) $validated['amount'], 2);
            $balance = round((float) $model->grand_total - $paid, 2);
            $model->update(['paid_amount' => $paid, 'balance_due' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partially_paid']);
            if ($model->event_id) {
                Event::whereKey($model->event_id)->increment('amount_received', $validated['amount']);
            }

            \App\Services\Notifier::roles(\App\Services\Notifier::FINANCE, (int) $model->company_id, 'payment_recorded',
                'Payment recorded', $payment->payment_number.' for '.number_format((float) $validated['amount'], 2).' against '.$model->invoice_number.($balance <= 0 ? ' (invoice fully paid)' : '').'.', '/invoices', $request->user()->id);

            return response()->json(['message' => 'Payment recorded.', 'data' => new PaymentResource($payment),
                'invoice' => new InvoiceResource($model->fresh())], 201);
        }, 3);
    }
}
