@extends('layouts.app')

@section('title', 'Client Portal Demo View')

@section('content')
<div class="space-y-6">

    <!-- Client Welcome Banner -->
    <div class="bg-gradient-to-r from-rose-900 via-rose-800 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10">
            <span class="text-xs uppercase tracking-widest font-bold text-amber-300 bg-white/10 px-3 py-1 rounded-full">Client Portal Access</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-3">Welcome, {{ $client->name ?? 'Valued Client' }}</h1>
            <p class="text-xs text-rose-100/80 mt-1 max-w-xl">Track your wedding & corporate event timelines, review approved quotations, download tax invoices, and monitor guest RSVPs in real time.</p>
        </div>
    </div>

    <!-- Client Events -->
    <div class="space-y-4">
        <h3 class="text-base font-extrabold text-slate-900">Your Booked Event Productions</h3>
        <div class="space-y-4">
            @if ($client)
                @foreach ($client->events as $evt)
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                            <div>
                                <span class="font-mono text-rose-600 font-bold text-xs bg-rose-50 px-2.5 py-0.5 rounded">{{ $evt->event_code }}</span>
                                <h4 class="text-lg font-extrabold text-slate-900 mt-1">{{ $evt->title }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <i class="fa-regular fa-calendar text-rose-500 mr-1"></i>{{ $evt->start_date->format('M d, Y') }} &bull;
                                    <i class="fa-solid fa-hotel text-slate-400 mr-1"></i>{{ $evt->venue->name ?? 'Venue Confirmed' }}
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 font-bold uppercase text-xs rounded-full self-start sm:self-auto">
                                {{ $evt->status }}
                            </span>
                        </div>

                        <!-- Schedule Highlights -->
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Run of Show Program</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                                @forelse ($evt->schedules->take(3) as $sch)
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                        <span class="text-[10px] font-bold text-rose-600 font-mono">{{ date('h:i A', strtotime($sch->start_time)) }}</span>
                                        <h5 class="font-bold text-slate-900 mt-0.5">{{ $sch->title }}</h5>
                                        <p class="text-[11px] text-slate-500 mt-1">{{ $sch->activity_description }}</p>
                                    </div>
                                @empty
                                    <p class="text-slate-400 text-xs">Program schedule is being finalized.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Financials & Invoices -->
                        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs gap-3">
                            <div>
                                <span class="text-slate-400">Total Contract:</span>
                                <span class="font-extrabold text-slate-900 ml-1">₹{{ number_format($evt->contract_value) }}</span>
                                <span class="text-slate-300 mx-2">|</span>
                                <span class="text-emerald-600 font-bold">₹{{ number_format($evt->amount_received) }} Paid</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('events.show', $evt) }}" class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg transition">
                                    View Full Itinerary &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

</div>
@endsection

