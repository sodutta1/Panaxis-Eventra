<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount('events', 'invoices');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $clients = $query->orderBy('name')->paginate(12);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'alternate_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'client_type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $clientCode = 'CL-' . rand(1000, 9999);

        Client::create(array_merge($validated, [
            'company_id' => $companyId,
            'client_code' => $clientCode,
            'status' => 'active'
        ]));

        return redirect()->route('clients.index')->with('success', 'Client profile registered.');
    }

    public function show(Client $client)
    {
        $client->load(['events.venue', 'proposals', 'quotations', 'invoices.payments']);
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'client_type' => 'required|string',
            'notes' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client details updated.');
    }
}

