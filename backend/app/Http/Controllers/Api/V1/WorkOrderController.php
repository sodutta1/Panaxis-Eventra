<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vendor;
use App\Models\VendorAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderController extends Controller
{
    use ScopesToCompany;

    private function findWo(Request $request, int $id): VendorAssignment
    {
        return $this->findForCompany(VendorAssignment::with(['vendor', 'event']), $request, $id);
    }

    public function index(Request $request, int $event)
    {
        $ev = $this->findForCompany(Event::query(), $request, $event);
        $orders = VendorAssignment::with('vendor')->where('event_id', $ev->id)->latest()->get();

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request, int $event)
    {
        $data = $request->validate([
            'vendor_id' => 'required|integer',
            'service_title' => 'required|string|max:255',
            'scope_of_work' => 'nullable|string|max:16000',
            'contract_amount' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
        ]);

        $ev = $this->findForCompany(Event::query(), $request, $event);
        $vendor = $this->findForCompany(Vendor::query(), $request, $data['vendor_id']);
        if (($vendor->status ?? null) === 'blacklisted') {
            abort(422, 'This vendor is blacklisted and cannot be assigned work.');
        }

        $wo = VendorAssignment::create([
            'company_id' => $ev->company_id,
            'event_id' => $ev->id,
            'vendor_id' => $vendor->id,
            'service_title' => $data['service_title'],
            'scope_of_work' => $data['scope_of_work'] ?? null,
            'contract_amount' => $data['contract_amount'],
            'paid_amount' => 0,
            'status' => 'assigned',
        ]);

        return response()->json(['data' => $wo->load('vendor')], 201);
    }

    public function addPayment(Request $request, int $workOrder)
    {
        $data = $request->validate(['amount' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99']);

        return DB::transaction(function () use ($request, $workOrder, $data) {
            $wo = $this->findForCompany(VendorAssignment::with(['vendor', 'event'])->lockForUpdate(), $request, $workOrder);
            if ($wo->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'amount' => 'Cannot record a payment on a cancelled work order.',
                ]);
            }
            // Reject overpayment outright; the caller must not believe more was
            // paid than the contract holds. Same contract as invoice payments.
            $remaining = round((float) $wo->contract_amount - (float) $wo->paid_amount, 2);
            if (round($data['amount'], 2) > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => "Payment exceeds the remaining balance of {$remaining}.",
                ]);
            }
            $newPaid = round((float) $wo->paid_amount + $data['amount'], 2);
            $wo->update([
                'paid_amount' => $newPaid,
                'status' => $newPaid >= (float) $wo->contract_amount ? 'paid' : ($wo->status === 'assigned' ? 'in_progress' : $wo->status),
            ]);

            return response()->json(['data' => $wo->fresh(['vendor'])]);
        }, 3);
    }

    public function updateStatus(Request $request, int $workOrder)
    {
        $data = $request->validate(['status' => 'required|string|in:assigned,in_progress,completed,paid,cancelled']);
        $wo = $this->findWo($request, $workOrder);
        if (! $wo->canTransitionTo($data['status'])) {
            abort(422, "Cannot move a work order from {$wo->status} to {$data['status']}.");
        }
        $wo->update(['status' => $data['status']]);

        return response()->json(['data' => $wo->fresh(['vendor'])]);
    }
}
