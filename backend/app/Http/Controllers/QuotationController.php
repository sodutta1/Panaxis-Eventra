<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Client;
use App\Models\Event;
use Carbon\Carbon;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with(['client', 'event'])->orderBy('created_at', 'desc')->paginate(15);
        return view('quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $events = Event::orderBy('title')->get();
        $selectedEventId = $request->event_id;

        return view('quotations.create', compact('clients', 'events', 'selectedEventId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'event_id' => 'nullable|exists:events,id',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'tax_rate' => 'required|numeric',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
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
        $quotationNumber = 'QTN-' . date('Y') . '-' . rand(1000, 9999);

        $quotation = Quotation::create([
            'company_id' => $companyId,
            'client_id' => $validated['client_id'],
            'event_id' => $validated['event_id'],
            'quotation_number' => $quotationNumber,
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => 'draft',
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            $totalPrice = $item['quantity'] * $item['unit_price'];
            $quotation->items()->create([
                'item_name' => $item['item_name'],
                'item_type' => 'service',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $totalPrice,
            ]);
        }

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation ' . $quotationNumber . ' created.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'event', 'items']);
        return view('quotations.show', compact('quotation'));
    }

    public function print(Quotation $quotation)
    {
        $quotation->load(['client', 'event', 'items']);
        return view('quotations.print', compact('quotation'));
    }
}

