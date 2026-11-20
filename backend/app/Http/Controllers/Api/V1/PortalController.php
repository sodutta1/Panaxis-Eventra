<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
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
            $query->where('user_id', $request->user()->id);
        } else {
            $this->scopeCompany($query, $request);
        }

        $client = $query->first();

        if (!$client) {
            return response()->json(['message' => 'No client account is linked to this user.'], 404);
        }

        return response()->json(['data' => $client]);
    }

    /**
     * Vendor portal: vendor profile with event assignments and expenses.
     * Note: the schema has no vendors.user_id link (the web portal has the
     * same limitation and shows the first vendor), so a vendor-role token
     * sees the first vendor of the company. A real login link is backend work.
     * GET /api/v1/portal/vendor
     */
    public function vendorPortal(Request $request)
    {
        $vendor = $this->scopeCompany(
            Vendor::with(['assignments.event', 'expenses.event', 'category']),
            $request
        )->first();

        if (!$vendor) {
            return response()->json(['message' => 'No vendor account is linked to this user.'], 404);
        }

        return response()->json(['data' => $vendor]);
    }
}
