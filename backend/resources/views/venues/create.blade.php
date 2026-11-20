@extends('layouts.app')

@section('title', 'Onboard Venue')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('venues.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Venues</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Onboard Venue Facility</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('venues.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Venue Property Name *</label>
                <input type="text" name="name" required placeholder="e.g. The Imperial Grand Palace" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Venue Type *</label>
                    <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                        <option value="banquet_hall">Banquet Hall</option>
                        <option value="hotel">Hotel Ballroom</option>
                        <option value="resort">Resort & Lawn</option>
                        <option value="convention_centre">Convention Center</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Guest Capacity *</label>
                    <input type="number" name="capacity" value="500" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Rental / Day (₹) *</label>
                    <input type="number" step="0.01" name="rental_cost_per_day" value="250000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">City *</label>
                    <input type="text" name="city" required placeholder="City Name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">State</label>
                    <input type="text" name="state" placeholder="State Name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Street Address</label>
                <input type="text" name="address" placeholder="Full Location Address" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Amenities & Infrastructure Details</label>
                <textarea name="amenities" rows="2" placeholder="e.g. Pillarless Hall, Dedicated Generator, 200 Car Valet, Green Rooms..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                <a href="{{ route('venues.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 font-semibold text-slate-600">Cancel</a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md">Add Venue</button>
            </div>
        </form>
    </div>

</div>
@endsection

