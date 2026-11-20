@extends('layouts.app')

@section('title', 'Create Quotation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    items: [
        { item_name: 'Turnkey Audio-Visual Rig & Staging', quantity: 1, unit_price: 250000 },
        { item_name: 'Thematic Stage Backdrop & Floral Setup', quantity: 1, unit_price: 350000 }
    ],
    taxRate: 18,
    addItem() {
        this.items.push({ item_name: '', quantity: 1, unit_price: 0 });
    },
    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },
    subtotal() {
        return this.items.reduce((sum, item) => sum + (Number(item.quantity) * Number(item.unit_price)), 0);
    },
    taxAmount() {
        return (this.subtotal() * this.taxRate) / 100;
    },
    grandTotal() {
        return this.subtotal() + this.taxAmount();
    }
}">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('quotations.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Quotations</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Generate Quotation / Cost Estimate</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('quotations.store') }}" method="POST" class="space-y-6 text-xs">
            @csrf

            <!-- Header Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Client Profile *</label>
                    <select name="client_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">Select Client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->company_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Link to Event Production</label>
                    <select name="event_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">None / Custom Proposal</option>
                        @foreach ($events as $e)
                            <option value="{{ $e->id }}" {{ $selectedEventId == $e->id ? 'selected' : '' }}>{{ $e->title }} ({{ $e->event_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Issue Date *</label>
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Valid Until</label>
                    <input type="date" name="valid_until" value="{{ date('Y-m-d', strtotime('+30 days')) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <!-- Dynamic Line Items -->
            <div>
                <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-sm text-slate-900 uppercase tracking-wider">Itemized Deliverables & Staging</h3>
                    <button type="button" @click="addItem()" class="text-rose-600 font-bold hover:text-rose-700 flex items-center space-x-1">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Add Line Item</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-12 gap-2 items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <div class="col-span-6">
                                <input type="text" :name="'items[' + index + '][item_name]'" x-model="item.item_name" required placeholder="Service / Package description" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="'items[' + index + '][quantity]'" x-model="item.quantity" min="1" required placeholder="Qty" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="col-span-3">
                                <input type="number" step="0.01" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" min="0" required placeholder="Rate (₹)" class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="col-span-1 text-center">
                                <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Financial Totals Summary -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 max-w-sm ml-auto space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Subtotal:</span>
                    <span class="font-bold text-slate-800" x-text="'₹' + subtotal().toLocaleString()"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">GST Rate (%):</span>
                    <input type="number" name="tax_rate" x-model="taxRate" class="w-16 text-right bg-white border border-slate-200 rounded px-2 py-0.5 font-bold">
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">GST Tax:</span>
                    <span class="font-bold text-slate-800" x-text="'₹' + taxAmount().toLocaleString()"></span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between text-sm">
                    <span class="font-extrabold text-slate-900">Grand Total:</span>
                    <span class="font-black text-rose-600" x-text="'₹' + grandTotal().toLocaleString()"></span>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Notes & Payment Milestones</label>
                <textarea name="notes" rows="2" placeholder="50% advance upon contract signing, 40% on stage load-in, 10% on completion." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                <a href="{{ route('quotations.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 font-semibold text-slate-600">Cancel</a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md">Create Quotation</button>
            </div>
        </form>
    </div>

</div>
@endsection

