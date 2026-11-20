@extends('layouts.app')

@section('title', 'Client Directory')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Client Relationship Management</h1>
            <p class="text-xs text-slate-500 mt-1">Corporate enterprises, high-net-worth individuals, and recurring event patrons.</p>
        </div>
        <a href="{{ route('clients.create') }}" class="inline-flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-md transition self-start sm:self-auto">
            <i class="fa-solid fa-user-plus"></i>
            <span>Register Client</span>
        </a>
    </div>

    <!-- Clients Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($clients as $client)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-amber-500 text-white font-bold flex items-center justify-center text-sm shadow">
                                {{ substr($client->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">
                                    <a href="{{ route('clients.show', $client) }}" class="hover:text-rose-600">{{ $client->name }}</a>
                                </h3>
                                <p class="text-[11px] text-slate-400">{{ $client->company_name ?? 'Individual' }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">
                            {{ $client->client_code }}
                        </span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1.5">
                        <p class="flex items-center"><i class="fa-solid fa-phone text-slate-400 w-4 text-center mr-2"></i>{{ $client->phone ?? 'No phone' }}</p>
                        <p class="flex items-center"><i class="fa-solid fa-envelope text-slate-400 w-4 text-center mr-2"></i>{{ $client->email ?? 'No email' }}</p>
                        <p class="flex items-center"><i class="fa-solid fa-location-dot text-slate-400 w-4 text-center mr-2"></i>{{ $client->city ?? 'Location N/A' }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">Productions: <strong>{{ $client->events_count }}</strong></span>
                    <a href="{{ route('clients.show', $client) }}" class="font-bold text-rose-600 hover:text-rose-700">
                        View Profile &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs">
                No clients found in the registry.
            </div>
        @endforelse
    </div>

</div>
@endsection

