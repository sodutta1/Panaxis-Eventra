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

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Lead::with('assignedUser'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        return LeadResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreLeadRequest $request)
    {
        $lead = Lead::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'status' => 'new',
        ]));

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $lead)
    {
        return new LeadResource($this->findForCompany(Lead::with('assignedUser'), $request, $lead));
    }

    public function update(UpdateLeadRequest $request, int $lead)
    {
        $model = $this->findForCompany(Lead::query(), $request, $lead);
        $model->update($request->validated());

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
