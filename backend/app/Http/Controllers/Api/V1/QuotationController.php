<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreQuotationRequest;
use App\Http\Resources\V1\QuotationResource;
use App\Models\Quotation;
use App\Services\Notifier;
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

    public function update(StoreQuotationRequest $request, int $quotation)
    {
        return DB::transaction(function () use ($request, $quotation) {
            $model = $this->findForCompany(Quotation::query(), $request, $quotation);

            if (! in_array($model->status, ['draft', 'sent'], true)) {
                return response()->json(['message' => 'Only draft or sent quotations can be edited.'], 409);
            }

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

            $model->update([
                'client_id' => $validated['client_id'],
                'event_id' => $validated['event_id'] ?? $model->event_id,
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'subtotal' => $subtotal,
                'tax_rate' => $validated['tax_rate'],
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            $model->items()->delete();
            foreach ($validated['items'] as $item) {
                $model->items()->create([
                    'item_name' => $item['item_name'],
                    'item_type' => 'service',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            return new QuotationResource($model->load(['items', 'client']));
        }, 3);
    }

    /** Staff: release a draft to the client. POST /quotations/{id}/send */
    public function send(Request $request, int $quotation)
    {
        $model = $this->findForCompany(Quotation::with('client'), $request, $quotation);
        if ($model->status !== 'draft') {
            return response()->json(['message' => 'Only a draft quotation can be sent.'], 409);
        }
        $model->update(['status' => 'sent', 'sent_at' => now()]);
        Notifier::user($model->client?->user_id, $model->company_id, 'quotation_sent',
            "New quotation {$model->quotation_number}", Notifier::rupees($model->grand_total).' awaiting your response.',
            '/portal/client?section=quotations', $request->user()->id);

        return new QuotationResource($model->load(['items', 'client']));
    }

    /** Client: accept or decline their own sent quotation. POST /portal/quotations/{id}/respond */
    public function respond(Request $request, int $quotation)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $user = $request->user();

        return DB::transaction(function () use ($request, $quotation, $data, $user) {
            $model = Quotation::where('company_id', $this->companyId($request))
                ->whereHas('client', fn ($q) => $q->where('user_id', $user->id))
                ->whereKey($quotation)->lockForUpdate()->firstOrFail();

            if ($model->status !== 'sent') {
                return response()->json(['message' => $model->status === 'draft'
                    ? 'This quotation has not been sent yet.'
                    : 'This quotation has already been '.$model->status.'.'], 409);
            }
            if ($model->valid_until && $model->valid_until < now()->toDateString()) {
                return response()->json(['message' => 'This quotation has expired. Ask your event team for a new one.'], 409);
            }

            $model->update([
                'status' => $data['decision'],
                'responded_at' => now(),
                'responded_by' => $user->id,
                'response_note' => $data['note'] ?? null,
                'response_source' => 'client_portal',
                'response_method' => null,
            ]);

            $verb = $data['decision'] === 'accepted' ? 'accepted' : 'declined';
            $clientName = $model->client->name ?? 'The client';
            Notifier::roles(Notifier::SALES, $model->company_id, 'quotation_'.$data['decision'],
                "Quotation {$model->quotation_number} {$verb}",
                "{$clientName} {$verb} it (".Notifier::rupees($model->grand_total).')'.(! empty($data['note']) ? ': '.$data['note'] : '.'),
                '/quotations', $user->id);

            return new QuotationResource($model->load(['items', 'client']));
        }, 3);
    }

    /**
     * Staff: record the client's answer given verbally, by phone, in person or on paper.
     * The trail marks it as recorded by the staff member, never as a client portal response.
     * POST /quotations/{id}/record-response
     */
    public function recordResponse(Request $request, int $quotation)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'method' => ['required', 'in:verbal,phone,in_person,written'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $user = $request->user();

        return DB::transaction(function () use ($request, $quotation, $data, $user) {
            $model = Quotation::with('client')->where('company_id', $this->companyId($request))
                ->whereKey($quotation)->lockForUpdate()->firstOrFail();

            if (! in_array($model->status, ['draft', 'sent'], true)) {
                return response()->json(['message' => 'This quotation has already been '.$model->status.'.'], 409);
            }

            $model->update([
                'status' => $data['decision'],
                'sent_at' => $model->sent_at ?? now(),
                'responded_at' => now(),
                'responded_by' => $user->id,
                'response_note' => $data['note'] ?? null,
                'response_source' => 'staff_recorded',
                'response_method' => $data['method'],
            ]);

            $verb = $data['decision'] === 'accepted' ? 'accepted' : 'declined';
            $clientName = $model->client->name ?? 'The client';
            Notifier::roles(Notifier::SALES, $model->company_id, 'quotation_'.$data['decision'],
                "Quotation {$model->quotation_number} {$verb}",
                "{$user->name} recorded that {$clientName} {$verb} it (".Notifier::rupees($model->grand_total).').',
                '/quotations', $user->id);

            return new QuotationResource($model->load(['items', 'client']));
        }, 3);
    }

    public function destroy(Request $request, int $quotation)
    {
        $this->findForCompany(Quotation::query(), $request, $quotation)->delete();

        return response()->json(['message' => 'Quotation deleted.']);
    }
}
