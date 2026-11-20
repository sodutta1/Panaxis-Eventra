<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Event;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'event']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15);
        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $events = Event::orderBy('title')->get();
        $selectedEventId = $request->event_id;

        return view('invoices.create', compact('clients', 'events', 'selectedEventId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'event_id' => 'nullable|exists:events,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'tax_rate' => 'required|numeric',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }

        $taxAmount = ($subtotal * $validated['tax_rate']) / 100;
        $grandTotal = $subtotal + $taxAmount;

        $companyId = auth()->user()->company_id ?? 1;
        $invNumber = 'INV-' . date('Y') . '-' . rand(1000, 9999);

        $invoice = Invoice::create([
            'company_id' => $companyId,
            'client_id' => $validated['client_id'],
            'event_id' => $validated['event_id'],
            'invoice_number' => $invNumber,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'paid_amount' => 0,
            'balance_due' => $grandTotal,
            'status' => 'unpaid',
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice ' . $invNumber . ' generated.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'event', 'items', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $invoice->balance_due,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'transaction_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $payNumber = 'PAY-' . date('Y') . '-' . rand(1000, 9999);

        Payment::create([
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'event_id' => $invoice->event_id,
            'payment_number' => $payNumber,
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'],
            'status' => 'completed',
            'notes' => $validated['notes'],
        ]);

        $newPaid = $invoice->paid_amount + $validated['amount'];
        $newBalance = max(0, $invoice->grand_total - $newPaid);
        $newStatus = ($newBalance <= 0) ? 'paid' : 'partially_paid';

        $invoice->update([
            'paid_amount' => $newPaid,
            'balance_due' => $newBalance,
            'status' => $newStatus,
        ]);

        // Also update event amount received if attached
        if ($invoice->event) {
            $invoice->event->increment('amount_received', $validated['amount']);
        }

        return back()->with('success', 'Payment of ₹' . number_format($validated['amount'], 2) . ' recorded successfully.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['client', 'event', 'items', 'payments']);
        return view('invoices.print', compact('invoice'));
    }
}

