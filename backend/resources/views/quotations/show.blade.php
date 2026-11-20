@extends('layouts.app')

@section('title', 'Quotation - ' . $quotation->quotation_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('quotations.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Quotations</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Quotation {{ $quotation->quotation_number }}</h1>
        </div>
        <a href="{{ route('quotations.print', $quotation) }}" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl shadow transition flex items-center space-x-1.5">
            <i class="fa-solid fa-print"></i>
            <span>Printable PDF / Print</span>
        </a>
    </div>

    <!-- Quotation Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 text-xs space-y-6">
        <div class="flex justify-between items-start border-b border-slate-100 pb-6">
            <div>
                <span class="font-extrabold text-xl text-slate-900">GrandVibe Events & Productions</span>
                <p class="text-slate-500 mt-1">Bandra Kurla Complex, Mumbai 400051</p>
                <p class="text-slate-400">GSTIN: 27AAACG1234F1Z5</p>
            </div>
            <div class="text-right">
                <span class="font-mono font-bold text-rose-600 text-sm block">{{ $quotation->quotation_number }}</span>
                <p class="text-slate-500">Date: {{ date('M d, Y', strtotime($quotation->issue_date)) }}</p>
                <p class="text-slate-400">Valid Until: {{ date('M d, Y', strtotime($quotation->valid_until)) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Quotation Prepared For:</span>
                <h4 class="font-bold text-slate-900 text-sm">{{ $quotation->client->name ?? 'N/A' }}</h4>
                <p class="text-slate-500">{{ $quotation->client->company_name ?? '' }}</p>
                <p class="text-slate-500">{{ $quotation->client->phone ?? '' }}</p>
            </div>
            @if ($quotation->event)
                <div class="text-right">
                    <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Target Production:</span>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $quotation->event->title }}</h4>
                    <p class="text-slate-500 font-mono">{{ $quotation->event->event_code }}</p>
                </div>
            @endif
        </div>

        <!-- Line Items -->
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider border-y border-slate-100">
                <tr>
                    <th class="py-2.5 px-3">Item Description</th>
                    <th class="py-2.5 px-3 text-center">Qty</th>
                    <th class="py-2.5 px-3 text-right">Unit Rate</th>
                    <th class="py-2.5 px-3 text-right">Total Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($quotation->items as $item)
                    <tr>
                        <td class="py-3 px-3 font-semibold text-slate-800">{{ $item->item_name }}</td>
                        <td class="py-3 px-3 text-center font-medium">{{ $item->quantity }}</td>
                        <td class="py-3 px-3 text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-bold text-slate-900">₹{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="border-t border-slate-100 pt-4 flex justify-end">
            <div class="w-64 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Taxable Subtotal:</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($quotation->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>GST ({{ $quotation->tax_rate }}%):</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($quotation->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-extrabold text-slate-900 pt-2 border-t border-slate-200">
                    <span>Grand Total:</span>
                    <span class="text-rose-600">₹{{ number_format($quotation->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        @if ($quotation->notes)
            <div class="p-3 bg-slate-50 rounded-xl text-slate-500 text-[11px] leading-relaxed">
                <strong>Notes & Conditions:</strong> {{ $quotation->notes }}
            </div>
        @endif
    </div>

</div>
@endsection

