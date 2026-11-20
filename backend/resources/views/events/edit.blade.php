@extends('layouts.app')

@section('title', 'Edit Event - ' . $event->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('events.show', $event) }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to {{ $event->event_code }}</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Edit Event Production</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('events.update', $event) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Event Title *</label>
                    <input type="text" name="title" value="{{ old('title', $event->title) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Client Profile *</label>
                    <select name="client_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $event->client_id) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Event Category *</label>
                    <select name="event_category_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('event_category_id', $event->event_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Event Type *</label>
                    <input type="text" name="event_type" value="{{ old('event_type', $event->event_type) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Status *</label>
                    <select name="status" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="confirmed" {{ $event->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="in_planning" {{ $event->status === 'in_planning' ? 'selected' : '' }}>In Planning</option>
                        <option value="active" {{ $event->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="proposed" {{ $event->status === 'proposed' ? 'selected' : '' }}>Proposed</option>
                        <option value="enquiry" {{ $event->status === 'enquiry' ? 'selected' : '' }}>Enquiry</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Start Date & Time *</label>
                    <input type="datetime-local" name="start_date" value="{{ old('start_date', $event->start_date->format('Y-m-d\TH:i')) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">End Date & Time *</label>
                    <input type="datetime-local" name="end_date" value="{{ old('end_date', $event->end_date->format('Y-m-d\TH:i')) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Venue Location</label>
                    <select name="venue_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">No Venue</option>
                        @foreach ($venues as $venue)
                            <option value="{{ $venue->id }}" {{ old('venue_id', $event->venue_id) == $venue->id ? 'selected' : '' }}>{{ $venue->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lead Event Manager</label>
                    <select name="manager_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">Unassigned</option>
                        @foreach ($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('manager_id', $event->manager_id) == $mgr->id ? 'selected' : '' }}>{{ $mgr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Agreed Contract Value (₹)</label>
                    <input type="number" step="0.01" name="contract_value" value="{{ old('contract_value', $event->contract_value) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Confirmed Guests Count</label>
                    <input type="number" name="confirmed_guests" value="{{ old('confirmed_guests', $event->confirmed_guests) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Theme & Concept</label>
                    <input type="text" name="theme_concept" value="{{ old('theme_concept', $event->theme_concept) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-rose-500">{{ old('description', $event->description) }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('events.show', $event) }}" class="px-5 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md">Save Changes</button>
            </div>
        </form>
    </div>

</div>
@endsection

