<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreVenueRequest;
use App\Http\Requests\V1\UpdateVenueRequest;
use App\Http\Resources\V1\VenueResource;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Venue::query(), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }


        return $this->listResponse($query, $request, VenueResource::class);
    }

    public function store(StoreVenueRequest $request)
    {
        $venue = Venue::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'status' => 'available',
        ]));

        return (new VenueResource($venue))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $venue)
    {
        return new VenueResource($this->findForCompany(Venue::query(), $request, $venue));
    }

    public function update(UpdateVenueRequest $request, int $venue)
    {
        $model = $this->findForCompany(Venue::query(), $request, $venue);
        $model->update($request->validated());

        return new VenueResource($model->fresh());
    }

    public function destroy(Request $request, int $venue)
    {
        $this->findForCompany(Venue::query(), $request, $venue)->delete();

        return response()->json(['message' => 'Venue deleted.']);
    }
}
