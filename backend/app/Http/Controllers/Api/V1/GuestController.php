<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreGuestRequest;
use App\Http\Resources\V1\GuestResource;
use App\Models\EventGuest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(EventGuest::query(), $request);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('rsvp_status')) {
            $query->where('rsvp_status', $request->rsvp_status);
        }

        return GuestResource::collection($query->orderBy('name')->paginate(15));
    }

    public function store(StoreGuestRequest $request)
    {
        $guest = EventGuest::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'invitation_code' => 'INV-' . strtoupper(Str::random(6)),
            'rsvp_status' => 'pending',
            'checked_in' => false,
        ]));

        return (new GuestResource($guest))->response()->setStatusCode(201);
    }

    public function toggleCheckIn(Request $request, int $guest)
    {
        $model = $this->findForCompany(EventGuest::query(), $request, $guest);
        $model->update([
            'checked_in' => !$model->checked_in,
            'checked_in_at' => $model->checked_in ? null : Carbon::now(),
        ]);

        return new GuestResource($model->fresh());
    }

    public function show(Request $request, int $guest)
    {
        return new GuestResource($this->findForCompany(EventGuest::query(), $request, $guest));
    }
}
