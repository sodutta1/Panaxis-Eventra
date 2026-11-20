<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventGuest;
use App\Models\GuestGroup;
use App\Models\Event;
use Carbon\Carbon;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $query = EventGuest::with(['event', 'group']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('rsvp_status')) {
            $query->where('rsvp_status', $request->rsvp_status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('invitation_code', 'like', "%{$s}%");
            });
        }

        $guests = $query->orderBy('name')->paginate(20);
        $events = Event::orderBy('title')->get();

        return view('guests.index', compact('guests', 'events'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'guest_group_id' => 'nullable|exists:guest_groups,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'plus_ones' => 'nullable|integer|min:0',
            'table_number' => 'nullable|string',
            'dietary_preference' => 'required|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $invitationCode = 'GUEST-' . strtoupper(substr(md5(uniqid()), 0, 6));

        EventGuest::create(array_merge($validated, [
            'company_id' => $companyId,
            'invitation_code' => $invitationCode,
            'rsvp_status' => 'pending',
            'checked_in' => false,
        ]));

        return back()->with('success', 'Guest invited successfully with code ' . $invitationCode);
    }

    public function toggleCheckIn(EventGuest $guest)
    {
        $newChecked = !$guest->checked_in;
        $guest->update([
            'checked_in' => $newChecked,
            'checked_in_at' => $newChecked ? Carbon::now() : null,
        ]);

        return back()->with('success', $guest->name . ($newChecked ? ' checked in successfully!' : ' check-in reverted.'));
    }
}

