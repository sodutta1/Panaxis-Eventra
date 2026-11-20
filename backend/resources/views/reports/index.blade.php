@extends('layouts.app')

@section('title', 'Business Reports & Analytics')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Executive Intelligence & Analytics</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time database analytics covering margins, category turnover, and vendor expenditures.</p>
        </div>
    </div>

    <!-- Profitability & Margin Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Contract Value</span>
            <div class="text-2xl font-black text-slate-900 mt-1">₹{{ number_format($totalRevenue / 100000, 2) }}L</div>
            <span class="text-[11px] text-slate-400">Total pipeline across 7 productions</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Direct Spend</span>
            <div class="text-2xl font-black text-rose-600 mt-1">₹{{ number_format($totalCost / 100000, 2) }}L</div>
            <span class="text-[11px] text-slate-400">Vendor advances & venue costs</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gross Operating Margin</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">₹{{ number_format($grossProfit / 100000, 2) }}L</div>
            <span class="text-[11px] text-emerald-700 font-bold">{{ $profitMargin }}% Profit Margin</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Realized Cash Collections</span>
            <div class="text-2xl font-black text-purple-600 mt-1">₹{{ number_format(\App\Models\Payment::where('status', 'completed')->sum('amount') / 100000, 2) }}L</div>
            <span class="text-[11px] text-slate-400">Bank accounts & RTGS reconciled</span>
        </div>
    </div>

    <!-- Category Turnover & Top Stakeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Revenue by Category -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="font-extrabold text-base text-slate-900 mb-4">Turnover by Event Category</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3 text-center">Events</th>
                            <th class="py-2.5 px-3 text-right">Contract Value</th>
                            <th class="py-2.5 px-3 text-right">Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($revenueByCategory as $rc)
                            <tr>
                                <td class="py-3 px-3 font-bold text-slate-800">{{ $rc->name }}</td>
                                <td class="py-3 px-3 text-center font-semibold text-slate-600">{{ $rc->event_count }}</td>
                                <td class="py-3 px-3 text-right font-black text-slate-900">₹{{ number_format($rc->total_contract) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-600">₹{{ number_format($rc->total_received) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Clients & Vendors -->
        <div class="space-y-6">
            <!-- Top Clients -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-extrabold text-base text-slate-900 mb-3">Key Account Clients</h3>
                <div class="space-y-3 text-xs">
                    @foreach ($topClients as $tc)
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                            <div>
                                <span class="font-bold text-slate-900">{{ $tc->name }}</span>
                                <div class="text-[11px] text-slate-400">{{ $tc->company_name ?? 'Individual' }} &bull; {{ $tc->events_count }} Productions</div>
                            </div>
                            <span class="font-extrabold text-slate-900 text-sm">₹{{ number_format($tc->events_sum_contract_value ?? 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Vendors -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-extrabold text-base text-slate-900 mb-3">Top Vendor Disbursals</h3>
                <div class="space-y-3 text-xs">
                    @foreach ($topVendors as $tv)
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
                            <div>
                                <span class="font-bold text-slate-900">{{ $tv->name }}</span>
                                <div class="text-[11px] text-slate-400">{{ $tv->business_name }} &bull; {{ $tv->assignments_count }} Contracts</div>
                            </div>
                            <span class="font-bold text-rose-600">₹{{ number_format($tv->expenses_sum_amount ?? 0) }} Disbursed</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
