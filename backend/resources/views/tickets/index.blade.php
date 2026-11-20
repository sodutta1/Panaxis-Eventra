@extends('layouts.app')

@section('title', 'Ticketing & QR Gate Validator')

@section('content')
<div class="space-y-6" x-data="{
    showIssueModal: false,
    showTierModal: false,
    scannerMode: 'manual',
    selectedEventId: '{{ $allEvents->first()->id ?? '' }}',
    cameraRunning: false,
    scanMessage: null,
    scanStatus: null,
    init() {
        this.updateTiers();
    },
    updateTiers() {
        // Handled via Alpine event change
    }
}">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Ticketing, Passes & QR Gate Validator</h1>
            <p class="text-xs text-slate-500 mt-1">Issue event admission passes, manage tier capacities, and scan QR tokens at turnstiles.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button @click="showTierModal = true" class="inline-flex items-center space-x-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold px-3.5 py-2 rounded-xl shadow-sm transition">
                <i class="fa-solid fa-layer-group text-amber-400"></i>
                <span>Add Ticket Tier</span>
            </button>
            <button @click="showIssueModal = true" class="inline-flex items-center space-x-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-md transition">
                <i class="fa-solid fa-ticket"></i>
                <span>+ Issue Ticket Pass</span>
            </button>
        </div>
    </div>

    <!-- Live Gate Entrance Scanner Center -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 text-white shadow-xl border border-slate-700 relative overflow-hidden">
        <div class="max-w-2xl mx-auto space-y-4">

            <!-- Scanner Mode Switcher -->
            <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-rose-600/30 text-rose-400 border border-rose-500/30 flex items-center justify-center text-sm shadow">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold tracking-tight">Live Gate Entrance Scanner</h3>
                        <p class="text-[11px] text-slate-400">Validate attendee passes at entrance turnstiles.</p>
                    </div>
                </div>

                <div class="flex space-x-1 bg-slate-800 p-1 rounded-xl border border-slate-700 text-xs">
                    <button type="button" @click="scannerMode = 'manual'; stopCamera();" :class="scannerMode === 'manual' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="px-3 py-1 rounded-lg transition">
                        <i class="fa-solid fa-barcode mr-1"></i> Barcode / Manual
                    </button>
                    <button type="button" @click="scannerMode = 'camera'; startCamera();" :class="scannerMode === 'camera' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="px-3 py-1 rounded-lg transition">
                        <i class="fa-solid fa-camera mr-1"></i> Camera Scanner
                    </button>
                </div>
            </div>

            <!-- Mode 1: Manual / Barcode Laser Reader -->
            <div x-show="scannerMode === 'manual'" class="space-y-3">
                <form action="{{ route('tickets.verify') }}" method="POST" class="flex gap-2">
                    @csrf
                    <div class="relative flex-1">
                        <i class="fa-solid fa-barcode absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input type="text" name="token" id="barcodeTokenInput" autofocus required placeholder="Scan barcode with laser reader or enter QR-..." class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-xs text-white placeholder-slate-500 font-mono focus:outline-none focus:border-rose-500">
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg transition flex items-center space-x-1.5 shrink-0">
                        <i class="fa-solid fa-check-to-slot"></i>
                        <span>Validate & Admit</span>
                    </button>
                </form>

                <div class="text-[11px] text-slate-400 flex flex-wrap items-center justify-center gap-2 pt-1">
                    <span>Quick Test Tokens:</span>
                    @foreach ($tickets->take(3) as $demoT)
                        <button type="button" onclick="document.getElementById('barcodeTokenInput').value='{{ $demoT->qr_token }}'" class="font-mono bg-slate-800 hover:bg-slate-700 px-2 py-0.5 rounded text-amber-300 border border-slate-700 transition">
                            {{ $demoT->qr_token }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Mode 2: Live Webcam / Phone Camera Scanner -->
            <div x-show="scannerMode === 'camera'" class="space-y-3" x-cloak>
                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-700 text-center">
                    <div id="qr-reader" class="mx-auto rounded-xl overflow-hidden max-w-sm text-slate-800 bg-white"></div>
                    <p class="text-[11px] text-slate-400 mt-2">Point webcam or phone camera at the QR code printed on the attendee pass.</p>
                </div>

                <!-- Instant Camera Result Display -->
                <div x-show="scanMessage" x-cloak class="p-3 rounded-xl text-xs font-semibold flex items-center justify-between"
                     :class="scanStatus === 'success' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : (scanStatus === 'warning' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30')">
                    <span x-text="scanMessage"></span>
                    <button type="button" @click="scanMessage = null" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>

        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ticket #, name, QR..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
            </div>

            <div>
                <select name="event_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    <option value="">All Productions</option>
                    @foreach ($allEvents as $evt)
                        <option value="{{ $evt->id }}" {{ request('event_id') == $evt->id ? 'selected' : '' }}>{{ $evt->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="ticket_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    <option value="">All Statuses</option>
                    <option value="valid" {{ request('ticket_status') === 'valid' ? 'selected' : '' }}>Valid (Pending Entry)</option>
                    <option value="checked_in" {{ request('ticket_status') === 'checked_in' ? 'selected' : '' }}>Admitted (Checked In)</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs py-2 rounded-xl transition">
                    Filter Roster
                </button>
                <a href="{{ route('tickets.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tickets Roster Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Issued Tickets ({{ $tickets->total() }})</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Ticket # & QR Token</th>
                        <th class="px-4 py-3">Attendee Name</th>
                        <th class="px-4 py-3">Production & Tier</th>
                        <th class="px-4 py-3">Price Paid</th>
                        <th class="px-4 py-3">Admission Gate Status</th>
                        <th class="px-4 py-3 text-right">Pass Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($tickets as $t)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-mono font-bold text-slate-900">{{ $t->ticket_number }}</div>
                                <div class="font-mono font-bold text-rose-600 text-[11px] flex items-center space-x-1">
                                    <span>{{ $t->qr_token }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900">{{ $t->holder_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $t->holder_email ?? $t->holder_phone ?? 'Standard Pass' }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-medium text-slate-800">{{ $t->event->title ?? '-' }}</span>
                                <div class="text-[10px] text-purple-600 font-bold uppercase">{{ $t->ticketType->name ?? 'Pass' }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-slate-900">{{ $t->price_paid > 0 ? '₹' . number_format($t->price_paid) : 'Free' }}</span>
                                <span class="text-[10px] text-emerald-600 block capitalize">({{ $t->payment_status }})</span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($t->ticket_status === 'checked_in')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 flex items-center w-max space-x-1">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        <span>Admitted {{ $t->checked_in_at ? $t->checked_in_at->format('h:i A') : '' }}</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                        Valid (Pending)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-1">
                                <a href="{{ route('tickets.print', $t) }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 font-semibold rounded-lg border border-slate-200 transition inline-flex items-center space-x-1" title="Print E-Ticket Pass">
                                    <i class="fa-solid fa-print text-[10px]"></i>
                                    <span>Print Pass</span>
                                </a>

                                @if ($t->ticket_status !== 'checked_in')
                                    <form action="{{ route('tickets.verify') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $t->qr_token }}">
                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm transition inline-flex items-center space-x-1" title="Admit Attendee">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            <span>Admit</span>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No tickets found in registry.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $tickets->links() }}
        </div>
    </div>

    <!-- MODAL: ISSUE TICKET PASS -->
    <div x-show="showIssueModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showIssueModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Issue / Generate Event Ticket</h3>
                    <p class="text-slate-500 text-[11px]">Generate a verified admission pass linked to an event and tier.</p>
                </div>
                <button type="button" @click="showIssueModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form action="{{ route('tickets.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Select Event Production *</label>
                    <select name="event_id" x-model="selectedEventId" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        @foreach ($allEvents as $evt)
                            <option value="{{ $evt->id }}">{{ $evt->title }} ({{ $evt->start_date->format('d M Y') }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Ticket Tier / Category *</label>
                    <select name="ticket_type_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        @foreach ($ticketTypes as $tt)
                            <option value="{{ $tt->id }}">
                                {{ $tt->event->title ?? '' }} &rarr; {{ $tt->name }} (₹{{ number_format($tt->price) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Attendee / Holder Full Name *</label>
                    <input type="text" name="holder_name" required placeholder="e.g. Mr. Karan Johar" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Attendee Email</label>
                        <input type="email" name="holder_email" placeholder="attendee@example.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Attendee Phone</label>
                        <input type="text" name="holder_phone" placeholder="+91 ..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" max="10" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Price Paid (₹)</label>
                        <input type="number" step="0.01" name="price_paid" placeholder="Leave blank for tier rate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Status</label>
                        <select name="payment_status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                            <option value="paid">Paid</option>
                            <option value="complimentary">Complimentary / VIP</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showIssueModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Issue & Generate Pass</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD TICKET TIER -->
    <div x-show="showTierModal" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div @click.away="showTierModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Add New Ticket Tier</h3>
                    <p class="text-slate-500 text-[11px]">Define pricing and quota for an event.</p>
                </div>
                <button type="button" @click="showTierModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form action="{{ route('tickets.types.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Event Production *</label>
                    <select name="event_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                        @foreach ($allEvents as $evt)
                            <option value="{{ $evt->id }}">{{ $evt->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tier Title *</label>
                    <input type="text" name="name" required placeholder="e.g. VIP Backstage Lounge Pass" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ticket Price (₹) *</label>
                        <input type="number" step="0.01" name="price" value="2500" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Total Capacity / Quota *</label>
                        <input type="number" name="total_capacity" value="250" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-rose-500">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Pass Perks & Benefits</label>
                    <textarea name="benefits" rows="2" placeholder="e.g. Fast-track queue, complimentary food & beverage voucher..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showTierModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-md">Create Tier</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<!-- HTML5 QR Code Scanner Library for Webcams / Mobile Cameras -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrCode = null;

    function startCamera() {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText, decodedResult) => {
                // QR code successfully decoded
                console.log("Scanned QR Token: ", decodedText);
                submitScannedToken(decodedText);
            },
            (errorMessage) => {
                // scanning...
            }
        ).catch((err) => {
            console.error("Camera error: ", err);
        });
    }

    function stopCamera() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                console.log("Camera stopped.");
            }).catch(err => console.error(err));
        }
    }

    function submitScannedToken(token) {
        // Send AJAX verify request
        fetch("{{ route('tickets.verify') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ token: token })
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(result => {
            const data = result.body;
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));

            if (result.status === 200) {
                alpineData.scanStatus = 'success';
                alpineData.scanMessage = "✅ " + data.message;
            } else if (result.status === 409) {
                alpineData.scanStatus = 'warning';
                alpineData.scanMessage = "⚠️ " + data.message;
            } else {
                alpineData.scanStatus = 'error';
                alpineData.scanMessage = "❌ " + data.message;
            }

            // Play quick audio beep
            playBeep(result.status === 200);

            // Reload page after 2 seconds to refresh counts
            if (result.status === 200) {
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    function playBeep(success) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = success ? 880 : 330;
            gain.gain.value = 0.15;
            osc.start();
            setTimeout(() => { osc.stop(); }, 200);
        } catch (e) {}
    }
</script>
@endsection
