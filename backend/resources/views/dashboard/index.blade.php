@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Greeting -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Operations & Executive Command</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time overview of current productions, revenue pipeline, and guest operations.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('events.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition">
                <i class="fa-solid fa-plus"></i>
                <span>Create New Event</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="inline-flex items-center space-x-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-sm transition">
                <i class="fa-solid fa-qrcode text-rose-400"></i>
                <span>Gate Scanner</span>
            </a>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Events -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Productions</span>
                <div class="flex items-baseline space-x-2 mt-1">
                    <span class="text-3xl font-black text-slate-800">{{ $activeEvents }}</span>
                    <span class="text-xs text-slate-400 font-medium">/ {{ $totalEvents }} Total</span>
                </div>
                <div class="mt-2 flex items-center text-[11px] text-emerald-600 font-semibold">
                    <i class="fa-solid fa-arrow-trend-up mr-1"></i>
                    <span>All venues reserved</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-champagne-glasses"></i>
            </div>
        </div>

        <!-- Confirmed Revenue -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Confirmed Pipeline</span>
                <div class="flex items-baseline space-x-1 mt-1">
                    <span class="text-2xl font-black text-slate-800">₹{{ number_format($totalRevenue / 100000, 2) }}L</span>
                </div>
                <div class="mt-2 flex items-center text-[11px] text-slate-500">
                    <span class="font-medium text-emerald-600">₹{{ number_format($receivedRevenue / 100000, 2) }}L Received</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>

        <!-- Expenses & Procurement -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Vendor Expenses</span>
                <div class="flex items-baseline space-x-1 mt-1">
                    <span class="text-2xl font-black text-slate-800">₹{{ number_format($totalExpenses / 100000, 2) }}L</span>
                </div>
                <div class="mt-2 flex items-center text-[11px] text-amber-600 font-medium">
                    <i class="fa-solid fa-file-invoice mr-1"></i>
                    <span>₹{{ number_format($outstandingInvoices / 100000, 2) }}L Due from Clients</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- CRM & Leads -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">CRM Inquiries</span>
                <div class="flex items-baseline space-x-2 mt-1">
                    <span class="text-3xl font-black text-slate-800">{{ $totalLeads }}</span>
                    <span class="text-xs text-slate-400 font-medium">({{ $totalClients }} Active Clients)</span>
                </div>
                <div class="mt-2 flex items-center text-[11px] text-purple-600 font-medium">
                    <i class="fa-solid fa-users mr-1"></i>
                    <span>{{ $checkedInGuests }} Guests checked-in</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-bolt"></i>
            </div>
        </div>
    </div>

    <!-- Main Section: Upcoming Events & Task Milestones -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Upcoming Events Table -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">Upcoming Event Schedule</h3>
                    <p class="text-xs text-slate-500">Major productions scheduled across all regional branches.</p>
                </div>
                <a href="{{ route('events.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700">View All Events &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3">Event & Code</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Date & Venue</th>
                            <th class="px-4 py-3">Budget</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($upcomingEvents as $evt)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900 truncate max-w-xs">{{ $evt->title }}</div>
                                    <div class="text-[11px] font-mono text-rose-600">{{ $evt->event_code }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-800">{{ $evt->client->name ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $evt->category->name ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-800">{{ $evt->start_date->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[150px]">{{ $evt->venue->name ?? 'Venue Pending' }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900">₹{{ number_format($evt->contract_value) }}</div>
                                    <div class="text-[10px] text-emerald-600">₹{{ number_format($evt->amount_received) }} paid</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $badgeColor = match($evt->status) {
                                            'confirmed' => 'bg-emerald-100 text-emerald-800',
                                            'in_planning' => 'bg-blue-100 text-blue-800',
                                            'active' => 'bg-rose-100 text-rose-800 animate-pulse',
                                            'completed' => 'bg-slate-100 text-slate-700',
                                            'proposed' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $badgeColor }}">
                                        {{ str_replace('_', ' ', $evt->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <a href="{{ route('events.show', $evt) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 transition" title="View Full Details">
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Side: Milestone Tasks & Quick Category Breakdown -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Critical Production Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-base text-slate-900">Priority Milestones</h3>
                    <span class="text-xs bg-rose-50 text-rose-600 font-bold px-2 py-0.5 rounded-full">{{ $pendingTasks->count() }} Pending</span>
                </div>

                <div class="space-y-3">
                    @forelse ($pendingTasks as $task)
                        <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition flex items-start space-x-3">
                            <form action="{{ route('tasks.toggle', $task) }}" method="POST">
                                @csrf
                                <button type="submit" class="mt-0.5 w-4 h-4 rounded border border-slate-300 flex items-center justify-center hover:border-emerald-500 text-transparent hover:text-emerald-500">
                                    <i class="fa-solid fa-check text-[10px]"></i>
                                </button>
                            </form>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-800 line-clamp-1">{{ $task->title }}</p>
                                <div class="flex items-center space-x-2 mt-1 text-[11px] text-slate-400">
                                    <span class="text-rose-600 font-medium truncate">{{ $task->event->title ?? '' }}</span>
                                    <span>&bull;</span>
                                    <span class="text-slate-500">Due: {{ $task->due_date ? date('M d', strtotime($task->due_date)) : 'Today' }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $task->priority }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">No pending urgent milestones.</p>
                    @endforelse
                </div>
            </div>

            <!-- Categories Portfolio -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <h3 class="font-extrabold text-base text-slate-900 mb-3">Portfolio Breakdown</h3>
                <div class="space-y-2.5">
                    @foreach ($eventsByCategory as $cat)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">{{ $cat->name }}</span>
                            <span class="bg-slate-100 text-slate-800 font-bold px-2 py-0.5 rounded-full">{{ $cat->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

