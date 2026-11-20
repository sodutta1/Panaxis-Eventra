@extends('layouts.app')

@section('title', 'Equipment & Inventory')

@section('content')
<div class="space-y-6" x-data="{ showItemModal: false }">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Equipment Assets & Rental Inventory</h1>
            <p class="text-xs text-slate-500 mt-1">In-house LED video walls, sound systems, staging trusses, chairs, and props.</p>
        </div>
        <button @click="showItemModal = true" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Add Asset Item</span>
        </button>
    </div>

    <!-- Inventory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($items as $item)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-mono text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded uppercase">{{ $item->item_code }}</span>
                            <span class="text-[11px] text-slate-400 ml-1">{{ $item->category->name ?? 'Gear' }}</span>
                            <h3 class="font-extrabold text-base text-slate-900 mt-1.5">{{ $item->name }}</h3>
                        </div>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                            {{ $item->condition }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 my-4 p-3 bg-slate-50 rounded-xl text-center text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Total Stock</span>
                            <span class="font-black text-slate-800 text-base">{{ $item->total_quantity }} {{ $item->unit }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Available</span>
                            <span class="font-black text-emerald-600 text-base">{{ $item->available_quantity }} {{ $item->unit }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">
                        Rental Rate: <strong>₹{{ number_format($item->rental_rate) }} / {{ $item->unit }}</strong>
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Allocations: <strong>{{ $item->allocations_count }}</strong></span>
                    <span class="text-emerald-600 font-semibold text-[11px]"><i class="fa-solid fa-circle-check mr-1"></i>In Service</span>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
                No equipment items registered.
            </div>
        @endforelse
    </div>

    <!-- MODAL: ADD INVENTORY ITEM -->
    <div x-show="showItemModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showItemModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-4">Add Equipment Asset to Catalog</h3>
            <form action="{{ route('inventory.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Equipment Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Shure Axient Digital Wireless Mic Kit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Equipment Category</label>
                        <select name="inventory_category_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Unit</label>
                        <select name="unit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="sets">Sets / Kits</option>
                            <option value="meters">Meters</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Total Quantity *</label>
                        <input type="number" name="total_quantity" value="10" min="1" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Rental Rate (₹) *</label>
                        <input type="number" step="0.01" name="rental_rate" value="1500" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Condition Status</label>
                    <select name="condition" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="good">Good / Ready</option>
                        <option value="fair">Fair</option>
                        <option value="maintenance_required">Maintenance Required</option>
                    </select>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showItemModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

