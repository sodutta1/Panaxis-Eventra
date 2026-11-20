@extends('layouts.app')

@section('title', 'Venue Directory')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Partner Venues & Banquets</h1>
            <p class="text-xs text-slate-500 mt-1">Palaces, convention halls, hotel ballrooms, open-air lawns, and beachfront resorts.</p>
        </div>
        <a href="{{ route('venues.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Add Venue Location</span>
        </a>
    </div>

    <!-- Venues Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($venues as $venue)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-slate-100 bg-slate-50/40">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded uppercase">{{ $venue->venue_code }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Available</span>
                        </div>
                        <h3 class="font-extrabold text-base text-slate-900 mt-2">
                            <a href="{{ route('venues.show', $venue) }}" class="hover:text-rose-600">{{ $venue->name }}</a>
                        </h3>
                        <p class="text-xs text-slate-500 flex items-center mt-1">
                            <i class="fa-solid fa-location-dot mr-1.5 text-rose-500"></i>
                            <span>{{ $venue->city }}, {{ $venue->state }}</span>
                        </p>
                    </div>

                    <div class="p-5 space-y-2 text-xs text-slate-600">
                        <p><i class="fa-solid fa-users text-purple-500 w-4 mr-1"></i>Max Capacity: <strong>{{ number_format($venue->capacity) }} Guests</strong></p>
                        <p><i class="fa-solid fa-indian-rupee-sign text-emerald-600 w-4 mr-1"></i>Daily Rental: <strong>₹{{ number_format($venue->rental_cost_per_day) }}</strong></p>
                        <p class="text-slate-500 text-[11px] pt-1 leading-relaxed"><i class="fa-solid fa-circle-check text-slate-400 mr-1"></i>{{ $venue->amenities }}</p>
                    </div>
                </div>

                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Past Bookings: <strong>{{ $venue->events_count }}</strong></span>
                    <a href="{{ route('venues.show', $venue) }}" class="font-bold text-rose-600 hover:text-rose-700">
                        View Facility &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
                No venues onboarded yet.
            </div>
        @endforelse
    </div>

</div>
@endsection

