@extends('layouts.app')

@section('title', 'Invoices & Billing')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tax Invoices & GST Billing</h1>
            <p class="text-xs text-slate-500 mt-1">Milestone billing, payment receipts, balance tracking, and tax invoices.</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Create Tax Invoice</span>
        </a>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Invoice #</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Production</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Total Amount</th>
                        <th class="px-4 py-3">Balance Due</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($invoices as $inv)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $inv->client->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3.5 text-slate-600 truncate max-w-xs">{{ $inv->event->title ?? '-' }}</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ date('d M Y', strtotime($inv->due_date)) }}</td>
                            <td class="px-4 py-3.5 font-bold text-slate-900">₹{{ number_format($inv->grand_total) }}</td>
                            <td class="px-4 py-3.5 font-bold text-rose-600">₹{{ number_format($inv->balance_due) }}</td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-2">
                                <a href="{{ route('invoices.show', $inv) }}" class="font-bold text-rose-600 hover:text-rose-700">View & Pay</a>
                                <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="text-slate-400 hover:text-slate-700" title="Print Invoice"><i class="fa-solid fa-print"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No invoices generated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

