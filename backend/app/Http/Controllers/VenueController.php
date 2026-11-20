<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\VenueBooking;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $query = Venue::withCount('events', 'bookings');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $venues = $query->orderBy('name')->paginate(12);

        return view('venues.index', compact('venues'));
    }

    public function create()
    {
        return view('venues.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'rental_cost_per_day' => 'required|numeric|min:0',
            'contact_person' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'address' => 'nullable|string',
            'amenities' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $venueCode = 'VEN-' . rand(100, 999);

        Venue::create(array_merge($validated, [
            'company_id' => $companyId,
            'venue_code' => $venueCode,
            'status' => 'available',
        ]));

        return redirect()->route('venues.index')->with('success', 'Venue onboarded successfully.');
    }

    public function show(Venue $venue)
    {
        $venue->load(['events.client', 'bookings.event']);
        return view('venues.show', compact('venue'));
    }

    public function edit(Venue $venue)
    {
        return view('venues.edit', compact('venue'));
    }

    public function update(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'rental_cost_per_day' => 'required|numeric|min:0',
            'contact_person' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'address' => 'nullable|string',
            'amenities' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $venue->update($validated);

        return redirect()->route('venues.show', $venue)->with('success', 'Venue updated successfully.');
    }
}

