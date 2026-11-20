@extends('layouts.app')

@section('title', 'Event Expenses & Procurement')

@section('content')
<div class="space-y-6" x-data="{ showExpenseModal: false }">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Event Expenses & Procurement</h1>
            <p class="text-xs text-slate-500 mt-1">Track vendor advances, production purchases, licensing fees, and expense approvals.</p>
        </div>
        <button @click="showExpenseModal = true" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Record Expense</span>
        </button>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Expense #</th>
                        <th class="px-4 py-3">Expense Title</th>
                        <th class="px-4 py-3">Linked Production</th>
                        <th class="px-4 py-3">Vendor / Category</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($expenses as $exp)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900">{{ $exp->expense_number }}</td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $exp->title }}</td>
                            <td class="px-4 py-3.5 text-slate-600 truncate max-w-xs">{{ $exp->event->title ?? 'General Operational' }}</td>
                            <td class="px-4 py-3.5">
                                <span class="font-medium text-slate-800">{{ $exp->vendor->name ?? 'Direct' }}</span>
                                <div class="text-[10px] text-slate-400">{{ $exp->category->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500">{{ date('d M Y', strtotime($exp->incurred_date)) }}</td>
                            <td class="px-4 py-3.5 font-bold text-slate-900">₹{{ number_format($exp->amount, 2) }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                    {{ $exp->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No expenses recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: RECORD EXPENSE -->
    <div x-show="showExpenseModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showExpenseModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-4">Record Production Expense</h3>
            <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Expense Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Stage Truss Transport & Freight" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Linked Event Production</label>
                    <select name="event_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">General Corporate / Office</option>
                        @foreach ($events as $e)
                            <option value="{{ $e->id }}">{{ $e->title }} ({{ $e->event_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Expense Category</label>
                        <select name="expense_category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Vendor Beneficiary</label>
                        <select name="vendor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="">Direct / Self</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Amount (₹) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="50000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Date *</label>
                        <input type="date" name="incurred_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="bank_transfer">Bank Transfer / NEFT</option>
                            <option value="upi">UPI</option>
                            <option value="cash">Cash Voucher</option>
                            <option value="card">Corporate Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Receipt / Voucher #</label>
                        <input type="text" name="receipt_number" placeholder="VCH-123" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showExpenseModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Record Expense</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

