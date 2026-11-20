<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreClientRequest;
use App\Http\Requests\V1\UpdateClientRequest;
use App\Http\Resources\V1\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Client::withCount('events'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('client_code', 'like', "%{$search}%");
            });
        }

        return ClientResource::collection($query->orderBy('name')->paginate(15));
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'client_code' => 'CL-' . rand(1000, 9999),
            'status' => 'active',
        ]));

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $client)
    {
        return new ClientResource($this->findForCompany(Client::withCount('events'), $request, $client));
    }

    public function update(UpdateClientRequest $request, int $client)
    {
        $model = $this->findForCompany(Client::query(), $request, $client);
        $model->update($request->validated());

        return new ClientResource($model->fresh());
    }

    public function destroy(Request $request, int $client)
    {
        $this->findForCompany(Client::query(), $request, $client)->delete();

        return response()->json(['message' => 'Client deleted.']);
    }
}
