@extends('layouts.app')

@section('title', 'Quotations & Estimates')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Event Quotations & Cost Estimates</h1>
            <p class="text-xs text-slate-500 mt-1">Generate professional itemized quotations with GST calculations and client approval workflows.</p>
        </div>
        <a href="{{ route('quotations.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Create New Quote</span>
        </a>
    </div>

    <!-- Quotations Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Quote #</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Linked Event</th>
                        <th class="px-4 py-3">Issue Date</th>
                        <th class="px-4 py-3">Grand Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($quotations as $q)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-rose-600">{{ $q->quotation_number }}</td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $q->client->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3.5 text-slate-600">{{ $q->event->title ?? 'General Consultation' }}</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ date('d M Y', strtotime($q->issue_date)) }}</td>
                            <td class="px-4 py-3.5 font-extrabold text-slate-900">₹{{ number_format($q->grand_total) }}</td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $q->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $q->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-2">
                                <a href="{{ route('quotations.show', $q) }}" class="font-bold text-rose-600 hover:text-rose-700">View</a>
                                <a href="{{ route('quotations.print', $q) }}" target="_blank" class="text-slate-400 hover:text-slate-700" title="Print PDF"><i class="fa-solid fa-print"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No quotations recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

