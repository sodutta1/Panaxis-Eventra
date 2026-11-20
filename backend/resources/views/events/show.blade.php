@extends('layouts.app')

@section('title', $event->title)

@section('content')
<div class="space-y-6" x-data="{ currentTab: 'overview', showScheduleModal: false, showTaskModal: false, showGuestModal: false }">

    <!-- Event Header & Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs mb-1">
                    <span class="font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">{{ $event->event_code }}</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-semibold uppercase">{{ $event->category->name ?? 'General' }}</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="capitalize text-slate-500">{{ str_replace('_', ' ', $event->event_type) }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $event->title }}</h1>
                <p class="text-xs text-slate-500 mt-1 flex items-center flex-wrap gap-x-4 gap-y-1">
                    <span><i class="fa-regular fa-calendar text-rose-500 mr-1.5"></i>{{ $event->start_date->format('M d, Y h:i A') }} &rarr; {{ $event->end_date->format('M d, Y h:i A') }}</span>
                    <span><i class="fa-solid fa-hotel text-slate-400 mr-1.5"></i>{{ $event->venue->name ?? 'No Venue Selected' }} ({{ $event->venue->city ?? '' }})</span>
                    <span><i class="fa-solid fa-user-tie text-purple-500 mr-1.5"></i>Client: <strong>{{ $event->client->name ?? 'N/A' }}</strong></span>
                </p>
            </div>

            <!-- Status Transition & Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('events.status', $event) }}" method="POST" class="inline">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="text-xs font-bold uppercase tracking-wider px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:outline-none cursor-pointer">
                        <option value="enquiry" {{ $event->status === 'enquiry' ? 'selected' : '' }}>Status: Enquiry</option>
                        <option value="proposed" {{ $event->status === 'proposed' ? 'selected' : '' }}>Status: Proposed</option>
                        <option value="confirmed" {{ $event->status === 'confirmed' ? 'selected' : '' }}>Status: Confirmed</option>
                        <option value="in_planning" {{ $event->status === 'in_planning' ? 'selected' : '' }}>Status: In Planning</option>
                        <option value="active" {{ $event->status === 'active' ? 'selected' : '' }}>Status: Active Execution</option>
                        <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Status: Completed</option>
                        <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Status: Cancelled</option>
                    </select>
                </form>

                <a href="{{ route('events.edit', $event) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit</span>
                </a>

                <a href="{{ route('quotations.create', ['event_id' => $event->id]) }}" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Generate Quote</span>
                </a>
            </div>
        </div>

        <!-- Quick Top Financial Stats Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-100 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-400 block font-medium">Agreed Contract</span>
                <span class="text-base font-extrabold text-slate-900">₹{{ number_format($event->contract_value) }}</span>
            </div>
            <div class="p-3 bg-emerald-50 rounded-xl">
                <span class="text-emerald-700 block font-medium">Received Payments</span>
                <span class="text-base font-extrabold text-emerald-800">₹{{ number_format($event->amount_received) }}</span>
            </div>
            <div class="p-3 bg-rose-50 rounded-xl">
                <span class="text-rose-700 block font-medium">Actual Expenses</span>
                <span class="text-base font-extrabold text-rose-800">₹{{ number_format($event->expenses->sum('amount')) }}</span>
            </div>
            <div class="p-3 bg-purple-50 rounded-xl">
                <span class="text-purple-700 block font-medium">Attendance & RSVPs</span>
                <span class="text-base font-extrabold text-purple-900">{{ $event->guests->where('rsvp_status', 'attending')->count() }} / {{ $event->expected_guests }}</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-4 overflow-x-auto text-xs font-bold tracking-wide">
            <button @click="currentTab = 'overview'" :class="currentTab === 'overview' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-circle-info"></i>
                <span>Overview</span>
            </button>

            <button @click="currentTab = 'schedule'" :class="currentTab === 'schedule' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-clock"></i>
                <span>Run of Show ({{ $event->schedules->count() }})</span>
            </button>

            <button @click="currentTab = 'tasks'" :class="currentTab === 'tasks' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-list-check"></i>
                <span>Tasks ({{ $event->tasks->count() }})</span>
            </button>

            <button @click="currentTab = 'vendors'" :class="currentTab === 'vendors' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-handshake"></i>
                <span>Vendors ({{ $event->vendorAssignments->count() }})</span>
            </button>

            <button @click="currentTab = 'budget'" :class="currentTab === 'budget' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-coins"></i>
                <span>Budget & Costing</span>
            </button>

            <button @click="currentTab = 'guests'" :class="currentTab === 'guests' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-users"></i>
                <span>Guests & RSVPs ({{ $event->guests->count() }})</span>
            </button>

            <button @click="currentTab = 'tickets'" :class="currentTab === 'tickets' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-ticket"></i>
                <span>Tickets & Gates ({{ $event->ticketTypes->sum('sold_count') }})</span>
            </button>

            <button @click="currentTab = 'billing'" :class="currentTab === 'billing' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700'" class="py-3 px-2 border-b-2 transition uppercase flex items-center space-x-2">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Invoices ({{ $event->invoices->count() }})</span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div x-show="currentTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Info Cards -->
            <div class="md:col-span-2 space-y-6">
                <!-- Concept & Scope -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="font-extrabold text-sm text-slate-900 mb-2 uppercase tracking-wider">Concept, Design & Thematic Direction</h3>
                    <p class="text-xs text-rose-700 font-semibold mb-3 bg-rose-50 p-2.5 rounded-lg border border-rose-100">
                        <i class="fa-solid fa-palette mr-1.5"></i>{{ $event->theme_concept ?? 'Standard Elegance Theme' }}
                    </p>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $event->description ?? 'No detailed description provided for this production.' }}</p>
                </div>

                <!-- Catering & Menu Preview -->
                @if ($event->cateringMenus->count() > 0)
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-extrabold text-sm text-slate-900 uppercase tracking-wider">Catering & Banquet Menu</h3>
                            <span class="text-xs bg-amber-50 text-amber-700 font-bold px-2.5 py-0.5 rounded-full">Per Plate: ₹{{ number_format($event->cateringMenus->first()->per_plate_cost) }}</span>
                        </div>
                        @foreach ($event->cateringMenus as $menu)
                            <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-2">
                                <span class="font-bold text-slate-800 block text-sm">{{ $menu->menu_name }} ({{ ucfirst($menu->meal_type) }})</span>
                                <pre class="font-sans text-slate-600 whitespace-pre-wrap leading-relaxed text-xs">{{ $menu->menu_items }}</pre>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right Sidebar Stakeholders -->
            <div class="space-y-6">
                <!-- Client Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="font-extrabold text-xs text-slate-400 uppercase tracking-wider mb-3">Client Profile</h3>
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-sm">
                            {{ substr($event->client->name ?? 'C', 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900">{{ $event->client->name ?? 'Direct' }}</h4>
                            <p class="text-xs text-slate-400">{{ $event->client->company_name ?? 'Individual' }}</p>
                        </div>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1 pt-2 border-t border-slate-100">
                        <p><i class="fa-solid fa-phone text-slate-400 mr-2"></i>{{ $event->client->phone ?? 'N/A' }}</p>
                        <p><i class="fa-solid fa-envelope text-slate-400 mr-2"></i>{{ $event->client->email ?? 'N/A' }}</p>
                        <p><i class="fa-solid fa-city text-slate-400 mr-2"></i>{{ $event->client->city ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Venue Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="font-extrabold text-xs text-slate-400 uppercase tracking-wider mb-3">Venue Logistics</h3>
                    @if ($event->venue)
                        <h4 class="font-bold text-sm text-slate-900 mb-1">{{ $event->venue->name }}</h4>
                        <p class="text-xs text-slate-500 mb-2">{{ $event->venue->address }}, {{ $event->venue->city }}</p>
                        <div class="text-xs text-slate-600 space-y-1 pt-2 border-t border-slate-100">
                            <p><strong>Max Capacity:</strong> {{ number_format($event->venue->capacity) }} guests</p>
                            <p><strong>Day Rental:</strong> ₹{{ number_format($event->venue->rental_cost_per_day) }}</p>
                            <p><strong>Contact:</strong> {{ $event->venue->contact_person }} ({{ $event->venue->contact_phone }})</p>
                        </div>
                    @else
                        <p class="text-xs text-slate-400">No venue linked to this event yet.</p>
                    @endif
                </div>

                <!-- Manager Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="font-extrabold text-xs text-slate-400 uppercase tracking-wider mb-3">Production Lead</h3>
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs">
                            {{ substr($event->manager->name ?? 'M', 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900">{{ $event->manager->name ?? 'Unassigned' }}</h4>
                            <p class="text-[11px] text-slate-400">{{ $event->manager->phone ?? 'manager@eventflow.test' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: RUN OF SHOW SCHEDULE -->
    <div x-show="currentTab === 'schedule'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Minute-by-Minute Run of Show</h3>
            <button @click="showScheduleModal = true" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Schedule Block
            </button>
        </div>

        <div class="space-y-4">
            @forelse ($event->schedules as $sch)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start space-x-3">
                        <div class="bg-rose-50 text-rose-600 font-mono font-bold text-xs px-2.5 py-1.5 rounded-lg border border-rose-100 shrink-0">
                            {{ date('h:i A', strtotime($sch->start_time)) }}
                            @if ($sch->end_time)
                                &rarr; {{ date('h:i A', strtotime($sch->end_time)) }}
                            @endif
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900">{{ $sch->title }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $sch->activity_description }}</p>
                            <div class="flex items-center space-x-3 mt-2 text-[11px] text-slate-400">
                                <span><i class="fa-solid fa-location-dot text-rose-500 mr-1"></i>{{ $sch->location_within_venue ?? 'Main Stage' }}</span>
                                @if ($sch->speaker_performer)
                                    <span><i class="fa-solid fa-microphone text-purple-500 mr-1"></i>{{ $sch->speaker_performer }}</span>
                                @endif
                                @if ($sch->in_charge_person)
                                    <span><i class="fa-solid fa-user-check text-emerald-500 mr-1"></i>In Charge: {{ $sch->in_charge_person }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[10px] font-bold uppercase rounded-full">
                            {{ $sch->status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                    No run-of-show schedule entries added yet. Click "+ Add Schedule Block" to map the event itinerary.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 3: TASKS & CHECKLIST -->
    <div x-show="currentTab === 'tasks'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Production Checklist & Milestones</h3>
            <button @click="showTaskModal = true" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Production Task
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 w-10">Done</th>
                        <th class="px-4 py-3">Milestone Title</th>
                        <th class="px-4 py-3">Assigned Lead</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($event->tasks as $t)
                        <tr class="hover:bg-slate-50/70 {{ $t->status === 'completed' ? 'opacity-60 bg-slate-50/40' : '' }}">
                            <td class="px-5 py-3">
                                <form action="{{ route('tasks.toggle', $t) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-4 h-4 rounded border {{ $t->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300' }} flex items-center justify-center">
                                        @if ($t->status === 'completed')
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 font-semibold {{ $t->status === 'completed' ? 'line-through text-slate-400' : 'text-slate-900' }}">
                                {{ $t->title }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $t->assignedUser->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $t->due_date ? date('M d, Y', strtotime($t->due_date)) : 'Ongoing' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $t->priority === 'urgent' ? 'bg-red-100 text-red-700' : ($t->priority === 'high' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $t->priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="capitalize text-slate-500 font-medium">{{ str_replace('_', ' ', $t->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No production tasks assigned to this event.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: VENDOR ASSIGNMENTS -->
    <div x-show="currentTab === 'vendors'" class="space-y-4">
        <h3 class="text-base font-extrabold text-slate-900">Contracted Production Vendors & Partners</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($event->vendorAssignments as $va)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">{{ $va->vendor->category->name ?? 'Vendor' }}</span>
                            <h4 class="font-extrabold text-base text-slate-900 mt-1">{{ $va->vendor->name }}</h4>
                            <p class="text-xs text-slate-500">{{ $va->service_title }}</p>
                        </div>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">{{ $va->status }}</span>
                    </div>
                    <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">{{ $va->scope_of_work }}</p>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <div>
                            <span class="text-slate-400">Contract Amount:</span>
                            <span class="font-extrabold text-slate-900 ml-1">₹{{ number_format($va->contract_amount) }}</span>
                        </div>
                        <div>
                            <span class="text-emerald-700 font-semibold">₹{{ number_format($va->paid_amount) }} Paid</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                    No vendor contracts linked to this event.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 5: BUDGET & COSTING -->
    <div x-show="currentTab === 'budget'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Budget Allocations & Actual Spend</h3>
            @if ($event->budget)
                <span class="text-xs font-bold text-slate-600">Contingency Buffer: {{ $event->budget->contingency_percent }}% (₹{{ number_format($event->budget->contingency_amount) }})</span>
            @endif
        </div>

        @if ($event->budget && $event->budget->items->count() > 0)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3">Cost Category</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Estimated Budget</th>
                            <th class="px-4 py-3">Actual Spent</th>
                            <th class="px-4 py-3 text-right">Variance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($event->budget->items as $bi)
                            @php $variance = $bi->estimated_cost - $bi->actual_cost; @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-bold text-slate-900">{{ $bi->category }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $bi->item_name }}</td>
                                <td class="px-4 py-3 font-medium">₹{{ number_format($bi->estimated_cost) }}</td>
                                <td class="px-4 py-3 font-bold text-slate-900">₹{{ number_format($bi->actual_cost) }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $variance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $variance >= 0 ? '+₹' : '-₹' }}{{ number_format(abs($variance)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-2xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                Detailed budget line items are pending configuration for this event.
            </div>
        @endif
    </div>

    <!-- TAB 6: GUESTS & RSVPS -->
    <div x-show="currentTab === 'guests'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Guest List & Live Check-in Gate</h3>
            <button @click="showGuestModal = true" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Add Guest Pass
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Guest Name & Group</th>
                        <th class="px-4 py-3">Invitation Code</th>
                        <th class="px-4 py-3">RSVP Status</th>
                        <th class="px-4 py-3">Table</th>
                        <th class="px-4 py-3">Dietary</th>
                        <th class="px-4 py-3 text-right">Gate Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($event->guests as $g)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3">
                                <div class="font-bold text-slate-900">{{ $g->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $g->group->name ?? 'General' }} ({{ $g->plus_ones ? "+{$g->plus_ones} Guests" : 'Single' }})</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-rose-600 font-bold">{{ $g->invitation_code }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $g->rsvp_status === 'attending' ? 'bg-emerald-100 text-emerald-800' : ($g->rsvp_status === 'declined' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $g->rsvp_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $g->table_number ?? '-' }}</td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ $g->dietary_preference }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('guests.checkin', $g) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-lg text-xs font-bold uppercase transition {{ $g->checked_in ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700' }}">
                                        {{ $g->checked_in ? 'Checked In' : 'Admit Guest' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No guests on the list yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 7: TICKETS -->
    <div x-show="currentTab === 'tickets'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Ticketing Tiers & Registered Passes</h3>
            <div class="flex items-center space-x-2">
                <a href="{{ route('tickets.index', ['event_id' => $event->id]) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-qrcode mr-1"></i> Open Gate Scanner
                </a>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @forelse ($event->ticketTypes as $tt)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-sm text-slate-900">{{ $tt->name }}</h4>
                        <span class="text-sm font-black text-rose-600">₹{{ number_format($tt->price) }}</span>
                    </div>
                    <p class="text-xs text-slate-500">{{ $tt->benefits }}</p>
                    <div class="pt-2 flex items-center justify-between text-xs text-slate-600">
                        <span>Sold: <strong>{{ $tt->sold_count }}</strong> / {{ $tt->total_capacity }}</span>
                        <span class="text-emerald-600 font-bold">Revenue: ₹{{ number_format($tt->sold_count * $tt->price) }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                    This event does not have active public ticketing tiers.
                </div>
            @endforelse
        </div>

        @if ($event->tickets->count() > 0)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mt-4">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Issued Tickets for this Event ({{ $event->tickets->count() }})</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3">Ticket #</th>
                                <th class="px-4 py-3">Attendee</th>
                                <th class="px-4 py-3">Tier</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($event->tickets as $t)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-3 font-mono font-bold text-slate-900">{{ $t->ticket_number }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $t->holder_name }}</td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-800">{{ $t->ticketType->name ?? 'Pass' }}</span></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $t->ticket_status === 'checked_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $t->ticket_status === 'checked_in' ? 'Admitted' : 'Valid' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('tickets.print', $t) }}" target="_blank" class="px-2 py-1 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 font-semibold rounded-lg border border-slate-200 transition">
                                            <i class="fa-solid fa-print mr-1"></i> Print
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- TAB 8: BILLING & INVOICES -->
    <div x-show="currentTab === 'billing'" class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900">Invoices & GST Milestones</h3>
            <a href="{{ route('invoices.create', ['event_id' => $event->id]) }}" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                <i class="fa-solid fa-plus mr-1"></i> Create Tax Invoice
            </a>
        </div>

        <div class="space-y-3">
            @forelse ($event->invoices as $inv)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-slate-900 text-sm">{{ $inv->invoice_number }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $inv->status }}
                            </span>
                        </div>
                        <p class="text-slate-500 mt-1">Due Date: {{ date('M d, Y', strtotime($inv->due_date)) }}</p>
                    </div>

                    <div class="flex items-center space-x-6">
                        <div>
                            <span class="text-slate-400 block">Total Amount:</span>
                            <span class="font-extrabold text-slate-900 text-sm">₹{{ number_format($inv->grand_total) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Balance Due:</span>
                            <span class="font-bold text-rose-600">₹{{ number_format($inv->balance_due) }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('invoices.show', $inv) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 font-semibold transition">
                                View & Pay
                            </a>
                            <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="p-1.5 text-slate-400 hover:text-slate-700" title="Print GST Invoice">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                    No invoices generated for this event yet.
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL: ADD SCHEDULE -->
    <div x-show="showScheduleModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showScheduleModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-4">Add Run of Show Schedule Item</h3>
            <form action="{{ route('events.schedules.store', $event) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Session / Activity Title *</label>
                    <input type="text" name="title" required placeholder="e.g. VIP Reception & Sangeet Welcome" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Start Time *</label>
                        <input type="time" name="start_time" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">End Time</label>
                        <input type="time" name="end_time" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Location within Venue</label>
                    <input type="text" name="location_within_venue" placeholder="e.g. Grand Crystal Ballroom / Lawn" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Speaker / Performer / Artist</label>
                    <input type="text" name="speaker_performer" placeholder="e.g. DJ or Keynote Speaker" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Details & Cue Notes</label>
                    <textarea name="activity_description" rows="2" placeholder="Staging cues, lighting triggers, microphone requirements..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Add to Timeline</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD TASK -->
    <div x-show="showTaskModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showTaskModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-4">Add Production Task</h3>
            <form action="{{ route('events.tasks.store', $event) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Task Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Conduct Soundcheck for Line Arrays" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Priority</label>
                        <select name="priority" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assign Lead Staff</label>
                    <select name="assigned_to" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="">Unassigned</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showTaskModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Add Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD GUEST -->
    <div x-show="showGuestModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showGuestModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <h3 class="text-base font-extrabold text-slate-900 mb-4">Add Guest / Issue Invitation</h3>
            <form action="{{ route('guests.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Guest Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Mr. Sanjay Kapoor" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" placeholder="guest@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" placeholder="+91 98..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Guest Group</label>
                        <select name="guest_group_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="">General</option>
                            @foreach ($guestGroups as $gg)
                                <option value="{{ $gg->id }}">{{ $gg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Plus Ones</label>
                        <input type="number" name="plus_ones" value="0" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Table Number</label>
                        <input type="text" name="table_number" placeholder="T-01" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Dietary Preference</label>
                    <select name="dietary_preference" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        <option value="veg">Vegetarian</option>
                        <option value="non_veg">Non-Vegetarian</option>
                        <option value="vegan">Vegan</option>
                        <option value="jain">Jain Pure Veg</option>
                    </select>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="showGuestModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Generate Pass</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

