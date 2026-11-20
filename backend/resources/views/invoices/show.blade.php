@extends('layouts.app')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ showPaymentModal: false }">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('invoices.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Invoices</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Invoice {{ $invoice->invoice_number }}</h1>
        </div>
        <div class="flex items-center space-x-2">
            @if ($invoice->balance_due > 0)
                <button @click="showPaymentModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Record Payment</span>
                </button>
            @endif
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl shadow transition flex items-center space-x-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print GST Invoice</span>
            </a>
        </div>
    </div>

    <!-- Invoice Details Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 text-xs space-y-6">
        <div class="flex justify-between items-start border-b border-slate-100 pb-6">
            <div>
                <span class="font-extrabold text-xl text-slate-900">GrandVibe Events & Productions</span>
                <p class="text-slate-500 mt-1">Floor 12, Crescenzo Tower, BKC, Mumbai 400051</p>
                <p class="text-slate-400">GSTIN: 27AAACG1234F1Z5</p>
            </div>
            <div class="text-right">
                <span class="font-mono font-bold text-slate-900 text-base block">{{ $invoice->invoice_number }}</span>
                <p class="text-slate-500">Date: {{ date('M d, Y', strtotime($invoice->invoice_date)) }}</p>
                <p class="text-slate-500">Due: {{ date('M d, Y', strtotime($invoice->due_date)) }}</p>
                <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    Status: {{ $invoice->status }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Billed To:</span>
                <h4 class="font-bold text-slate-900 text-sm">{{ $invoice->client->name ?? 'N/A' }}</h4>
                <p class="text-slate-500">{{ $invoice->client->company_name ?? '' }}</p>
                <p class="text-slate-500">GSTIN: {{ $invoice->client->tax_number ?? 'Unregistered' }}</p>
            </div>
            @if ($invoice->event)
                <div class="text-right">
                    <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Production Project:</span>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $invoice->event->title }}</h4>
                    <p class="text-slate-500 font-mono">{{ $invoice->event->event_code }}</p>
                </div>
            @endif
        </div>

        <table class="w-full text-left">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider border-y border-slate-100">
                <tr>
                    <th class="py-2.5 px-3">Description</th>
                    <th class="py-2.5 px-3 text-center">Qty</th>
                    <th class="py-2.5 px-3 text-right">Unit Price</th>
                    <th class="py-2.5 px-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="py-3 px-3 font-semibold text-slate-800">{{ $item->description }}</td>
                        <td class="py-3 px-3 text-center">{{ $item->quantity }}</td>
                        <td class="py-3 px-3 text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-bold text-slate-900">₹{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Payment Summary -->
        <div class="border-t border-slate-100 pt-4 flex justify-between items-start">
            <div class="max-w-xs space-y-2">
                @if ($invoice->payments->count() > 0)
                    <span class="font-bold uppercase tracking-wider text-slate-400 text-[10px] block">Payment Receipts</span>
                    @foreach ($invoice->payments as $p)
                        <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-100 text-emerald-800 text-[11px]">
                            <strong>₹{{ number_format($p->amount, 2) }}</strong> via {{ strtoupper($p->payment_method) }} on {{ date('d M Y', strtotime($p->payment_date)) }}
                            <div class="text-[10px] text-emerald-600 font-mono">Ref: {{ $p->transaction_reference }}</div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="w-64 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Taxable Value:</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>GST ({{ $invoice->tax_rate }}%):</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-800 pt-1 border-t border-slate-200">
                    <span>Invoice Total:</span>
                    <span>₹{{ number_format($invoice->grand_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-600 font-bold">
                    <span>Paid to Date:</span>
                    <span>-₹{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-extrabold text-slate-900 pt-2 border-t border-slate-300">
                    <span>Balance Due:</span>
                    <span class="text-rose-600">₹{{ number_format($invoice->balance_due, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: RECORD PAYMENT -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showPaymentModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">Record Client Payment</h3>
            <p class="text-slate-500 mb-4">Outstanding balance: <strong class="text-rose-600">₹{{ number_format($invoice->balance_due, 2) }}</strong></p>
            <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount to Collect (₹) *</label>
                    <input type="number" step="0.01" name="amount" value="{{ $invoice->balance_due }}" max="{{ $invoice->balance_due }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:outline-none focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                            <option value="bank_transfer">Bank RTGS / NEFT</option>
                            <option value="upi">UPI / Instant Pay</option>
                            <option value="credit_card">Corporate Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Transaction Reference / UTR Number</label>
                    <input type="text" name="transaction_reference" placeholder="e.g. HDFC-RTGS-112233" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Payment remarks..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

