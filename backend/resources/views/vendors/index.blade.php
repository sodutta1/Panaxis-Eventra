@extends('layouts.app')

@section('title', 'Vendors Directory')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Suppliers & Vendor Partners</h1>
            <p class="text-xs text-slate-500 mt-1">Audio-visual staging companies, florists, luxury caterers, and cinematography crews.</p>
        </div>
        <a href="{{ route('vendors.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Register Vendor</span>
        </a>
    </div>

    <!-- Vendor Directory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($vendors as $vendor)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">{{ $vendor->category->name ?? 'General' }}</span>
                            <h3 class="font-extrabold text-base text-slate-900 mt-1.5">
                                <a href="{{ route('vendors.show', $vendor) }}" class="hover:text-rose-600">{{ $vendor->name }}</a>
                            </h3>
                            <p class="text-[11px] text-slate-400 font-mono">{{ $vendor->vendor_code }} &bull; {{ $vendor->business_name }}</p>
                        </div>
                        <div class="flex items-center text-amber-500 text-xs font-bold bg-amber-50 px-2 py-0.5 rounded-full">
                            <i class="fa-solid fa-star text-[10px] mr-1"></i>
                            <span>{{ number_format($vendor->rating, 1) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1.5">
                        <p class="flex items-center"><i class="fa-solid fa-phone text-slate-400 w-4 text-center mr-2"></i>{{ $vendor->phone ?? 'N/A' }}</p>
                        <p class="flex items-center"><i class="fa-solid fa-envelope text-slate-400 w-4 text-center mr-2"></i>{{ $vendor->email ?? 'N/A' }}</p>
                        <p class="flex items-center"><i class="fa-solid fa-city text-slate-400 w-4 text-center mr-2"></i>{{ $vendor->city ?? 'N/A' }}</p>
                    </div>

                    <p class="mt-2 text-[11px] text-slate-500 bg-slate-50 p-2 rounded-lg line-clamp-2">
                        {{ $vendor->services_offered }}
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Assigned Productions: <strong>{{ $vendor->assignments_count }}</strong></span>
                    <a href="{{ route('vendors.show', $vendor) }}" class="font-bold text-rose-600 hover:text-rose-700">
                        View Profile &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
                No vendors registered.
            </div>
        @endforelse
    </div>

</div>
@endsection

