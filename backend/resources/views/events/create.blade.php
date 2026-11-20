@extends('layouts.app')

@section('title', 'Create New Event')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('events.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Events Directory</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Onboard New Event Production</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('events.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Basic Information -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                    1. Event Identification & Client
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Event Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Royal Heritage Wedding of Aryan & Sanjana" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Client Profile *</label>
                        <select name="client_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="">Select Existing Client</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->company_name ?: $client->client_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Event Category *</label>
                        <select name="event_category_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('event_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Event Type *</label>
                        <select name="event_type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="wedding">Wedding & Pre-wedding</option>
                            <option value="conference">Corporate Conference / Summit</option>
                            <option value="concert">Live Music Concert / EDM</option>
                            <option value="birthday">Birthday & Milestone Gala</option>
                            <option value="festival">College & Youth Festival</option>
                            <option value="exhibition">Exhibition & Trade Fair</option>
                            <option value="product_launch">Product Launch / Keynote</option>
                            <option value="private_party">Private VIP Party</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Production Status *</label>
                        <select name="status" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="confirmed" selected>Confirmed (Contract Signed)</option>
                            <option value="in_planning">In Planning Phase</option>
                            <option value="proposed">Proposed / Quotation Sent</option>
                            <option value="enquiry">Enquiry / Early Lead</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Venue & Dates -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                    2. Schedule, Venue & Logistics
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Start Date & Time *</label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date', date('Y-m-d\TH:i')) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">End Date & Time *</label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date', date('Y-m-d\TH:i', strtotime('+2 days'))) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Primary Venue</label>
                        <select name="venue_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="">Select Venue Location</option>
                            @foreach ($venues as $venue)
                                <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>{{ $venue->name }} ({{ $venue->city }} - Cap: {{ $venue->capacity }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Assigned Lead Event Director</label>
                        <select name="manager_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="">Assign Event Manager</option>
                            @foreach ($managers as $mgr)
                                <option value="{{ $mgr->id }}" {{ old('manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->name }} ({{ ucfirst($mgr->role) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Expected Guests / Attendance</label>
                        <input type="number" name="expected_guests" value="{{ old('expected_guests', 200) }}" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Total Agreed Contract Value (₹)</label>
                        <input type="number" step="0.01" name="contract_value" value="{{ old('contract_value', 1500000) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Concept & Theme -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                    3. Concept, Design & Theme Brief
                </h3>
                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Theme & Color Palette Concept</label>
                        <input type="text" name="theme_concept" value="{{ old('theme_concept') }}" placeholder="e.g. Royal Rajasthani Palace Theme with Brass and Rose Quartz" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Description & Scope of Work</label>
                        <textarea name="description" rows="3" placeholder="Overview of client requirements, staging details, artist line-ups..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs focus:outline-none focus:border-rose-500">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('events.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition">
                    Create & Initialize Production
                </button>
            </div>

        </form>
    </div>

</div>
@endsection

