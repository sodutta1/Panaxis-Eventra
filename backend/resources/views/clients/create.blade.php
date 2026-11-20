@extends('layouts.app')

@section('title', 'Register Client')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 mb-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Clients</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Register Client Account</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('clients.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Primary Contact Name *</label>
                    <input type="text" name="name" required placeholder="Full Name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Organization / Family Name</label>
                    <input type="text" name="company_name" placeholder="e.g. Oberoi Enterprises" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" placeholder="client@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Mobile Number</label>
                    <input type="text" name="phone" placeholder="+91 ..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">City</label>
                    <input type="text" name="city" placeholder="e.g. Mumbai" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Client Category</label>
                    <select name="client_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500">
                        <option value="individual">Individual / Private</option>
                        <option value="wedding">Wedding Family</option>
                        <option value="corporate">Corporate Organization</option>
                        <option value="agency">Event Production Agency</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Billing Address</label>
                <textarea name="address" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-2">
                <a href="{{ route('clients.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 font-semibold text-slate-600">Cancel</a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md">Register Client</button>
            </div>
        </form>
    </div>

</div>
@endsection

