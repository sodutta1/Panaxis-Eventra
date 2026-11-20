<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Client;
use App\Models\User;
use App\Models\LeadActivity;
use App\Models\EventCategory;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with(['client', 'assignedUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(15);
        $users = User::all();

        return view('leads.index', compact('leads', 'users'));
    }

    public function create()
    {
        $clients = Client::all();
        $users = User::all();
        return view('leads.create', compact('clients', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'required|string',
            'event_type' => 'nullable|string',
            'event_date' => 'nullable|date',
            'estimated_guests' => 'nullable|integer',
            'estimated_budget' => 'nullable|numeric',
            'assigned_to' => 'nullable|exists:users,id',
            'requirements' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;

        $lead = Lead::create(array_merge($validated, [
            'company_id' => $companyId,
            'status' => 'new'
        ]));

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'note',
            'details' => 'Lead created in system from source: ' . $validated['source'],
            'status' => 'completed',
        ]);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $request->validate(['status' => 'required|string']);
        $lead->update(['status' => $request->status]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'note',
            'details' => 'Lead status updated to ' . ucfirst($request->status),
            'status' => 'completed',
        ]);

        return back()->with('success', 'Lead status transitioned to ' . ucfirst($request->status));
    }

    public function convert(Lead $lead)
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Create client if not attached
        $client = Client::create([
            'company_id' => $companyId,
            'client_code' => 'CL-' . rand(1000, 9999),
            'name' => $lead->contact_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'client_type' => 'individual',
            'status' => 'active',
            'notes' => 'Converted from lead: ' . $lead->title,
        ]);

        $lead->update([
            'client_id' => $client->id,
            'status' => 'converted'
        ]);

        return redirect()->route('events.create')->with('success', 'Lead converted to Client ' . $client->name . '. You may now create the confirmed event.');
    }
}

