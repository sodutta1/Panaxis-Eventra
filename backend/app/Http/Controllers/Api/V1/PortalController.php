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
    use Concerns\QueriesLists;

    public function records(Request $request,string $kind){
        $role=$request->user()->role;$admin=in_array($role,['super_admin','company_admin','admin']);
        if($kind==='client'){
            abort_unless($admin||$role==='client',403);
            $client=$this->scopeCompany(Client::query(),$request);if(!$admin)$client->where('user_id',$request->user()->id);$client=$client->firstOrFail();
            $section=$request->input('section','events');abort_unless(in_array($section,['events','invoices','quotations']),404);
            $query=$client->$section()->getQuery();$query->where($query->getModel()->getTable().'.company_id',$this->companyId($request));
            $resource=['events'=>\App\Http\Resources\V1\EventResource::class,'invoices'=>\App\Http\Resources\V1\InvoiceResource::class,'quotations'=>\App\Http\Resources\V1\QuotationResource::class][$section];
            return $this->listResponse($query,$request,$resource);
        }
        abort_unless($kind==='vendor',404);
        abort_unless($admin,403,'Vendor account linking is not configured. Contact your company administrator.');
        $vendor=$this->scopeCompany(Vendor::query(),$request)->firstOrFail();$section=$request->input('section','work-orders');abort_unless(in_array($section,['work-orders','expenses']),404);
        $q=$section==='expenses'?$vendor->expenses()->getQuery():$vendor->assignments()->getQuery();$q->where('company_id',$this->companyId($request));
        return $this->listResponse($q,$request,$section==='expenses'?\App\Http\Resources\V1\ExpenseResource::class:null);
    }

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
