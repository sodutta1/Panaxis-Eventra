@extends('layouts.app')

@section('title', 'CRM & Leads Pipeline')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">CRM Enquiries & Leads Pipeline</h1>
            <p class="text-xs text-slate-500 mt-1">Track prospective client enquiries, manage conversion status, and generate proposals.</p>
        </div>
        <a href="{{ route('leads.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Log New Enquiry</span>
        </a>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Pipeline</span>
            <div class="flex space-x-2 text-xs">
                <a href="{{ route('leads.index') }}" class="px-2.5 py-1 rounded-lg {{ !request('status') ? 'bg-slate-800 text-white font-bold' : 'text-slate-500 hover:bg-slate-100' }}">All</a>
                <a href="{{ route('leads.index', ['status' => 'new']) }}" class="px-2.5 py-1 rounded-lg {{ request('status') === 'new' ? 'bg-slate-800 text-white font-bold' : 'text-slate-500 hover:bg-slate-100' }}">New</a>
                <a href="{{ route('leads.index', ['status' => 'qualified']) }}" class="px-2.5 py-1 rounded-lg {{ request('status') === 'qualified' ? 'bg-slate-800 text-white font-bold' : 'text-slate-500 hover:bg-slate-100' }}">Qualified</a>
                <a href="{{ route('leads.index', ['status' => 'converted']) }}" class="px-2.5 py-1 rounded-lg {{ request('status') === 'converted' ? 'bg-slate-800 text-white font-bold' : 'text-slate-500 hover:bg-slate-100' }}">Converted</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Inquiry Title</th>
                        <th class="px-4 py-3">Contact Person</th>
                        <th class="px-4 py-3">Event Date & Type</th>
                        <th class="px-4 py-3">Est. Budget</th>
                        <th class="px-4 py-3">Stage</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $lead->title }}</div>
                                <div class="text-[11px] text-slate-400">Source: <span class="capitalize text-slate-600 font-medium">{{ $lead->source }}</span></div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $lead->contact_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $lead->phone ?? $lead->email }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-slate-800">{{ $lead->event_date ? date('M d, Y', strtotime($lead->event_date)) : 'Date TBD' }}</div>
                                <div class="text-[11px] text-rose-600 font-semibold uppercase">{{ str_replace('_', ' ', $lead->event_type) }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-900">
                                ₹{{ number_format($lead->estimated_budget) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <form action="{{ route('leads.status', $lead) }}" method="POST">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border border-slate-200 bg-slate-50 cursor-pointer focus:outline-none">
                                        <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New</option>
                                        <option value="contacted" {{ $lead->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                        <option value="qualified" {{ $lead->status === 'qualified' ? 'selected' : '' }}>Qualified</option>
                                        <option value="proposal_sent" {{ $lead->status === 'proposal_sent' ? 'selected' : '' }}>Proposal Sent</option>
                                        <option value="negotiating" {{ $lead->status === 'negotiating' ? 'selected' : '' }}>Negotiating</option>
                                        <option value="converted" {{ $lead->status === 'converted' ? 'selected' : '' }}>Converted</option>
                                        <option value="lost" {{ $lead->status === 'lost' ? 'selected' : '' }}>Lost</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-1">
                                @if ($lead->status !== 'converted')
                                    <form action="{{ route('leads.convert', $lead) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-200 transition">
                                            Convert &rarr;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-emerald-600 font-bold text-xs"><i class="fa-solid fa-check-double mr-1"></i> Converted</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No leads recorded in pipeline.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

