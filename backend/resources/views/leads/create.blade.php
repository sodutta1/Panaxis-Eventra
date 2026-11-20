@extends('layouts.app')

@section('title', 'Log New Enquiry')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('leads.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Leads</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Log Event Enquiry / Lead</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('leads.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Inquiry Title *</label>
                <input type="text" name="title" required placeholder="e.g. 3-Day Destination Wedding in Goa" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Prospect Contact Name *</label>
                    <input type="text" name="contact_name" required placeholder="Full Name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Inquiry Source</label>
                    <select name="source" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                        <option value="website">Website Form</option>
                        <option value="referral">Word of Mouth / Referral</option>
                        <option value="social_media">Instagram / LinkedIn</option>
                        <option value="phone">Direct Call</option>
                        <option value="exhibition">Exhibition / Event Stall</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" placeholder="email@domain.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="+91 ..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Event Type</label>
                    <select name="event_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                        <option value="wedding">Wedding</option>
                        <option value="conference">Conference</option>
                        <option value="concert">Concert</option>
                        <option value="birthday">Birthday</option>
                        <option value="product_launch">Launch</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Target Date</label>
                    <input type="date" name="event_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Est. Budget (₹)</label>
                    <input type="number" step="0.01" name="estimated_budget" placeholder="1000000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Client Requirements & Brief</label>
                <textarea name="requirements" rows="3" placeholder="Brief of client expectations, venue preferences, guest size..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                <a href="{{ route('leads.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 font-semibold text-slate-600">Cancel</a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md">Record Lead</button>
            </div>
        </form>
    </div>

</div>
@endsection

