@extends('layouts.app')

@section('title', $vendor->name)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('vendors.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Vendors</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $vendor->name }}</h1>
            <p class="text-xs text-slate-500 font-mono">{{ $vendor->vendor_code }} &bull; {{ $vendor->category->name ?? 'Vendor' }}</p>
        </div>
    </div>

    <!-- Vendor Engagements & Assignments -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-extrabold text-base text-slate-900 mb-4">Contracted Production Assignments</h3>
        <div class="space-y-3">
            @forelse ($vendor->assignments as $va)
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded">{{ $va->event->event_code ?? '' }}</span>
                        <h4 class="font-bold text-sm text-slate-900 mt-1">{{ $va->service_title }}</h4>
                        <p class="text-xs text-slate-500">Event: <strong>{{ $va->event->title ?? 'N/A' }}</strong></p>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-sm text-slate-900 block">₹{{ number_format($va->contract_amount) }}</span>
                        <span class="text-xs text-emerald-600 font-semibold">₹{{ number_format($va->paid_amount) }} Disbursed</span>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 py-4 text-center">No active production assignments for this vendor.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection

