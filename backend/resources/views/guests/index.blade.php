@extends('layouts.app')

@section('title', 'Guest & RSVP Management')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Guest List, Invitations & RSVPs</h1>
            <p class="text-xs text-slate-500 mt-1">Manage VIP and family guest rosters, dietary preferences, table allocations, and gate admissions.</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('guests.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search guest name, code, email..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
            </div>

            <div>
                <select name="event_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    <option value="">All Productions</option>
                    @foreach ($events as $evt)
                        <option value="{{ $evt->id }}" {{ request('event_id') == $evt->id ? 'selected' : '' }}>{{ $evt->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs py-2 rounded-xl transition">
                    Filter Guests
                </button>
                <a href="{{ route('guests.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Guests Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Guest & Group</th>
                        <th class="px-4 py-3">Production Event</th>
                        <th class="px-4 py-3">Invitation Code</th>
                        <th class="px-4 py-3">RSVP Status</th>
                        <th class="px-4 py-3">Table #</th>
                        <th class="px-4 py-3">Dietary</th>
                        <th class="px-4 py-3 text-right">Gate Check-in</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($guests as $g)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $g->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $g->group->name ?? 'General Roster' }} ({{ $g->plus_ones ? "+{$g->plus_ones} Guests" : 'Single' }})</div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 truncate max-w-xs">{{ $g->event->title ?? '-' }}</td>
                            <td class="px-4 py-3.5 font-mono font-bold text-rose-600">{{ $g->invitation_code }}</td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $g->rsvp_status === 'attending' ? 'bg-emerald-100 text-emerald-800' : ($g->rsvp_status === 'declined' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $g->rsvp_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-800">{{ $g->table_number ?? '-' }}</td>
                            <td class="px-4 py-3.5 capitalize text-slate-600">{{ $g->dietary_preference }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <form action="{{ route('guests.checkin', $g) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-lg text-xs font-bold uppercase transition {{ $g->checked_in ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700' }}">
                                        {{ $g->checked_in ? 'Checked In' : 'Admit' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No guests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

