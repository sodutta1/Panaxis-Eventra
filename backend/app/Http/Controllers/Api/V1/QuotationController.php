<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreQuotationRequest;
use App\Http\Resources\V1\QuotationResource;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Quotation::with('client'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->listResponse($query, $request, QuotationResource::class);
    }

    public function store(StoreQuotationRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = round($subtotal + round($item['quantity'] * $item['unit_price'], 2), 2);
            }
            $taxAmount = round(($subtotal * $validated['tax_rate']) / 100, 2);
            $grandTotal = round($subtotal + $taxAmount, 2);

            if ($grandTotal > 9999999999.99) {
                throw ValidationException::withMessages(['items' => 'Document total exceeds the supported amount.']);
            }

            $quotation = Quotation::create([
                'company_id' => $this->companyId($request),
                'client_id' => $validated['client_id'],
                'event_id' => $validated['event_id'] ?? null,
                'quotation_number' => 'QTN-'.date('Y').'-'.Str::upper((string) Str::ulid()),
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
                    'total_price' => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            return (new QuotationResource($quotation->load(['items', 'client'])))
                ->response()->setStatusCode(201);
        }, 3);
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
