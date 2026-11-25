<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreLeadRequest;
use App\Http\Requests\V1\UpdateLeadRequest;
use App\Http\Requests\V1\UpdateLeadStatusRequest;
use App\Http\Resources\V1\LeadResource;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Lead::with('assignedUser'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }


        return $this->listResponse($query, $request, LeadResource::class);
    }

    public function store(StoreLeadRequest $request)
    {
        $lead = Lead::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'status' => 'new',
        ]));

        $name = $lead->contact_name ?? 'A new lead';
        if ($lead->assigned_to) {
            \App\Services\Notifier::user((int) $lead->assigned_to, (int) $lead->company_id, 'lead_assigned', 'New lead assigned to you', $name.' is waiting for a first call.', '/leads', $request->user()->id);
        } else {
            \App\Services\Notifier::roles(\App\Services\Notifier::SALES, (int) $lead->company_id, 'lead_new', 'New unassigned lead', $name.' just came in.', '/leads', $request->user()->id);
        }

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $lead)
    {
        return new LeadResource($this->findForCompany(Lead::with('assignedUser'), $request, $lead));
    }

    public function update(UpdateLeadRequest $request, int $lead)
    {
        $model = $this->findForCompany(Lead::query(), $request, $lead);
        $before = $model->assigned_to;
        $model->update($request->validated());
        if ($model->assigned_to && (int) $model->assigned_to !== (int) $before) {
            \App\Services\Notifier::user((int) $model->assigned_to, (int) $model->company_id, 'lead_assigned', 'Lead assigned to you', ($model->contact_name ?? 'A lead').' was assigned to you.', '/leads', $request->user()->id);
        }

        return new LeadResource($model->fresh());
    }

    public function destroy(Request $request, int $lead)
    {
        $this->findForCompany(Lead::query(), $request, $lead)->delete();

        return response()->json(['message' => 'Lead deleted.']);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, int $lead)
    {
        $model = $this->findForCompany(Lead::query(), $request, $lead);
        $model->update(['status' => $request->status]);
        \App\Services\Notifier::user($model->assigned_to ? (int) $model->assigned_to : null, (int) $model->company_id, 'lead_status', 'Lead moved to '.str_replace('_', ' ', $request->status), ($model->contact_name ?? 'A lead').' was updated.', '/leads', $request->user()->id);

        return new LeadResource($model->fresh());
    }

    public function convert(Request $request, int $lead)
    {
        return DB::transaction(function () use ($request, $lead) {
            $model = $this->findForCompany(Lead::query()->lockForUpdate(), $request, $lead);
            $companyId = $this->companyId($request);

            if ($model->client_id) {
                return response()->json(['message' => 'Lead already converted.', 'data' => new LeadResource($model), 'client_id' => $model->client_id]);
            }

            $client = Client::create([
                'company_id' => $companyId,
                'client_code' => 'CL-'.rand(1000, 9999),
                'name' => $model->contact_name,
                'email' => $model->email,
                'phone' => $model->phone,
                'client_type' => 'individual',
                'status' => 'active',
                'notes' => 'Converted from lead: '.$model->title,
            ]);

            $model->update(['client_id' => $client->id, 'status' => 'converted']);

            return response()->json([
                'message' => 'Lead converted to client '.$client->name.'.',
                'data' => new LeadResource($model->fresh()),
                'client_id' => $client->id,
            ]);
        }, 3);
    }
}
