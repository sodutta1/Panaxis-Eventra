@extends('layouts.app')

@section('title', $venue->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('venues.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Venues</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $venue->name }}</h1>
            <p class="text-xs text-slate-500 font-mono">{{ $venue->venue_code }} &bull; {{ $venue->city }} &bull; Capacity: {{ number_format($venue->capacity) }} Guests</p>
        </div>
    </div>

    <!-- Venue Bookings Timeline -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-extrabold text-base text-slate-900 mb-4">Venue Productions & Schedule</h3>
        <div class="space-y-3">
            @forelse ($venue->events as $evt)
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded">{{ $evt->event_code }}</span>
                        <h4 class="font-bold text-sm text-slate-900 mt-1">{{ $evt->title }}</h4>
                        <p class="text-xs text-slate-500">{{ $evt->start_date->format('M d, Y') }} &rarr; {{ $evt->end_date->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-xs uppercase px-2.5 py-1 rounded-full bg-slate-200 text-slate-800">{{ $evt->status }}</span>
                        <a href="{{ route('events.show', $evt) }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 block mt-2">View Event &rarr;</a>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 py-4 text-center">No production bookings recorded for this facility yet.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection

