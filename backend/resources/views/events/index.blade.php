@extends('layouts.app')

@section('title', 'Events Directory')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Event Operations Directory</h1>
            <p class="text-xs text-slate-500 mt-1">Manage scheduled, live, and confirmed event productions across all categories.</p>
        </div>
        <a href="{{ route('events.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Create New Event</span>
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('events.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, code, client..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
            </div>

            <div>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    <option value="">All Statuses</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="in_planning" {{ request('status') === 'in_planning' ? 'selected' : '' }}>In Planning</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Execution</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="proposed" {{ request('status') === 'proposed' ? 'selected' : '' }}>Proposed</option>
                    <option value="enquiry" {{ request('status') === 'enquiry' ? 'selected' : '' }}>Enquiry</option>
                </select>
            </div>

            <div>
                <select name="category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs py-2 rounded-xl transition">
                    Filter Events
                </button>
                <a href="{{ route('events.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Events Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($events as $event)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Card Header -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/40">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full uppercase">{{ $event->event_code }}</span>
                                <span class="text-[11px] text-slate-400 ml-1.5 capitalize font-medium">{{ str_replace('_', ' ', $event->event_type) }}</span>
                            </div>
                            @php
                                $badgeColor = match($event->status) {
                                    'confirmed' => 'bg-emerald-100 text-emerald-800',
                                    'in_planning' => 'bg-blue-100 text-blue-800',
                                    'active' => 'bg-rose-100 text-rose-800 font-bold animate-pulse',
                                    'completed' => 'bg-slate-100 text-slate-700',
                                    'proposed' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full {{ $badgeColor }}">
                                {{ str_replace('_', ' ', $event->status) }}
                            </span>
                        </div>
                        <h3 class="font-extrabold text-base text-slate-900 mt-2 line-clamp-1">
                            <a href="{{ route('events.show', $event) }}" class="hover:text-rose-600 transition">{{ $event->title }}</a>
                        </h3>
                        <p class="text-xs text-slate-500 flex items-center mt-1">
                            <i class="fa-solid fa-user-tie mr-1.5 text-slate-400"></i>
                            <span class="font-medium text-slate-700 truncate">{{ $event->client->name ?? 'Direct Client' }}</span>
                        </p>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 space-y-3 text-xs text-slate-600">
                        <div class="flex items-center space-x-2">
                            <i class="fa-regular fa-calendar text-rose-500 w-4 text-center"></i>
                            <span>{{ $event->start_date->format('d M Y') }} &rarr; {{ $event->end_date->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-hotel text-slate-400 w-4 text-center"></i>
                            <span class="truncate">{{ $event->venue->name ?? 'Venue TBD' }} ({{ $event->venue->city ?? '' }})</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-users text-purple-500 w-4 text-center"></i>
                            <span>{{ $event->confirmed_guests ?: $event->expected_guests }} Expected Guests</span>
                        </div>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-400">Contract Value:</span>
                            <span class="font-black text-slate-900 text-sm">₹{{ number_format($event->contract_value) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-[11px] text-slate-400">
                        Mgr: <span class="text-slate-600 font-medium">{{ $event->manager->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <a href="{{ route('events.show', $event) }}" class="px-3 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 font-semibold text-xs rounded-lg transition">
                            Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200 text-center">
                <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-3"></i>
                <h4 class="font-bold text-slate-700">No events matched your search criteria</h4>
                <p class="text-xs text-slate-400 mt-1">Try clearing filters or register a new event.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $events->links() }}
    </div>

</div>
@endsection

