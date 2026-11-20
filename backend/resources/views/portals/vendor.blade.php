@extends('layouts.app')

@section('title', 'Vendor Partner Portal')

@section('content')
<div class="space-y-6">

    <!-- Vendor Welcome Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10">
            <span class="text-xs uppercase tracking-widest font-bold text-indigo-300 bg-white/10 px-3 py-1 rounded-full">Vendor Partner Access</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-3">Welcome, {{ $vendor->name ?? 'Production Partner' }}</h1>
            <p class="text-xs text-indigo-100/80 mt-1 max-w-xl">Review event work orders, equipment load-in timings, contract disbursements, and technical specifications.</p>
        </div>
    </div>

    <!-- Active Production Assignments -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-extrabold text-base text-slate-900 mb-4">Assigned Work Orders & Contracts</h3>
        <div class="space-y-4">
            @if ($vendor)
                @forelse ($vendor->assignments as $va)
                    <div class="p-5 rounded-2xl border border-slate-100 bg-slate-50 space-y-3 text-xs">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded">{{ $va->event->event_code ?? '' }}</span>
                                <h4 class="font-extrabold text-base text-slate-900 mt-1">{{ $va->service_title }}</h4>
                                <p class="text-slate-500 font-medium">Event: {{ $va->event->title ?? 'N/A' }}</p>
                            </div>
                            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 uppercase font-bold text-[10px] rounded-full">
                                {{ $va->status }}
                            </span>
                        </div>

                        <p class="text-slate-600 bg-white p-3 rounded-xl border border-slate-200 leading-relaxed">
                            <strong>Technical Scope:</strong> {{ $va->scope_of_work }}
                        </p>

                        <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-slate-400">Total Purchase Order:</span>
                                <span class="font-black text-slate-900 text-sm ml-1">₹{{ number_format($va->contract_amount) }}</span>
                            </div>
                            <div>
                                <span class="text-emerald-600 font-bold">₹{{ number_format($va->paid_amount) }} Advance Received</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">No active work orders for your account.</p>
                @endforelse
            @endif
        </div>
    </div>

</div>
@endsection

