<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreQuotationRequest;
use App\Http\Resources\V1\QuotationResource;
use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Quotation::with('client'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return QuotationResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreQuotationRequest $request)
    {
        $validated = $request->validated();

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }
        $taxAmount = ($subtotal * $validated['tax_rate']) / 100;
        $grandTotal = $subtotal + $taxAmount;

        $quotation = Quotation::create([
            'company_id' => $this->companyId($request),
            'client_id' => $validated['client_id'],
            'event_id' => $validated['event_id'] ?? null,
            'quotation_number' => 'QTN-' . date('Y') . '-' . rand(1000, 9999),
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $quotation->items()->create([
                'item_name' => $item['item_name'],
                'item_type' => 'service',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return (new QuotationResource($quotation->load(['items', 'client'])))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $quotation)
    {
        return new QuotationResource($this->findForCompany(Quotation::with(['items', 'client']), $request, $quotation));
    }

    public function destroy(Request $request, int $quotation)
    {
        $this->findForCompany(Quotation::query(), $request, $quotation)->delete();

        return response()->json(['message' => 'Quotation deleted.']);
    }
}
