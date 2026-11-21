<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vendor;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    use ScopesToCompany;

    /**
     * Client portal: the client record linked to the token's user
     * (clients.user_id), with their events, run of show, invoices and quotations.
     * GET /api/v1/portal/client
     */
    public function clientPortal(Request $request)
    {
        $query = Client::with(['events.venue', 'events.days.schedules', 'invoices.payments', 'quotations']);

        if ($request->user()->role === 'client') {
            $query->where('user_id', $request->user()->id)->where('company_id', $this->companyId($request));
        } else {
            $this->scopeCompany($query, $request);
        }

        $client = $query->first();

        if (! $client) {
            return response()->json(['message' => 'No client account is linked to this user.'], 404);
        }

        return response()->json(['data' => $client]);
    }

    /**
     * Admin-only vendor preview. Vendor logins fail closed until the schema
     * has an explicit, tenant-scoped link between users and vendor profiles.
     */
    public function vendorPortal(Request $request)
    {
        if ($request->user()->role === 'vendor') {
            return response()->json(['message' => 'Vendor account linking is not configured. Contact your company administrator.'], 403);
        }
        $vendor = $this->scopeCompany(
            Vendor::with(['assignments.event', 'expenses.event', 'category']),
            $request
        )->first();

        if (! $vendor) {
            return response()->json(['message' => 'No vendor account is linked to this user.'], 404);
        }

        return response()->json(['data' => $vendor]);
    }
}
