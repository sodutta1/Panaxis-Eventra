<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket Pass - {{ $ticket->ticket_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .ticket-card { box-shadow: none !important; border: 2px dashed #cbd5e1 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center justify-center font-sans">

    <!-- Action Bar -->
    <div class="no-print max-w-xl w-full mb-4 flex items-center justify-between">
        <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1.5">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Ticket Roster</span>
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center space-x-2">
            <i class="fa-solid fa-print"></i>
            <span>Print E-Ticket Pass</span>
        </button>
    </div>

    <!-- Ticket Pass Card -->
    <div class="ticket-card max-w-xl w-full bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden relative">

        <!-- Header Band -->
        <div class="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 text-white p-6 relative overflow-hidden">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-rose-600 flex items-center justify-center text-white text-sm font-black shadow">
                        <i class="fa-solid fa-crown"></i>
                    </span>
                    <span class="font-extrabold text-sm tracking-tight">Event<span class="text-rose-500">Flow</span> ERP</span>
                </div>
                <span class="text-[10px] font-mono font-bold uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full text-rose-300">
                    Official Admission Pass
                </span>
            </div>

            <div class="mt-5 relative z-10">
                <span class="text-[10px] uppercase font-bold text-amber-300 tracking-wider">
                    {{ $ticket->event->category->name ?? 'Special Production' }}
                </span>
                <h1 class="text-xl sm:text-2xl font-black mt-0.5 leading-snug">{{ $ticket->event->title }}</h1>
                <p class="text-xs text-rose-200/90 font-mono mt-1">Code: {{ $ticket->event->event_code }}</p>
            </div>
        </div>

        <!-- Perforated Cut Line Graphic -->
        <div class="relative h-6 bg-white flex items-center justify-between px-[-12px]">
            <div class="w-6 h-6 rounded-full bg-slate-100 -ml-3 border-r border-slate-200"></div>
            <div class="flex-1 border-b-2 border-dashed border-slate-200 mx-2"></div>
            <div class="w-6 h-6 rounded-full bg-slate-100 -mr-3 border-l border-slate-200"></div>
        </div>

        <!-- Ticket Body -->
        <div class="p-6 pt-2 space-y-6">

            <!-- Event Details Grid -->
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Date & Time</span>
                    <p class="font-bold text-slate-800 text-sm">
                        {{ $ticket->event->start_date ? $ticket->event->start_date->format('l, M d, Y') : 'Date TBA' }}
                    </p>
                    <p class="text-slate-500 text-[11px]">
                        Gates Open: {{ $ticket->event->start_date ? $ticket->event->start_date->format('h:i A') : 'TBA' }}
                    </p>
                </div>

                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Venue Location</span>
                    <p class="font-bold text-slate-800 text-sm truncate">
                        {{ $ticket->event->venue->name ?? 'Venue Allocated on Site' }}
                    </p>
                    <p class="text-slate-500 text-[11px] truncate">
                        {{ $ticket->event->venue->address ?? '' }} {{ $ticket->event->venue->city ? '(' . $ticket->event->venue->city . ')' : '' }}
                    </p>
                </div>
            </div>

            <!-- Attendee Badge & Tier -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Ticket Holder</span>
                    <h3 class="font-black text-slate-900 text-base">{{ $ticket->holder_name }}</h3>
                    <p class="text-xs text-slate-500">{{ $ticket->holder_email ?? $ticket->holder_phone ?? 'Standard Pass' }}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold px-3 py-1 rounded-full bg-purple-100 text-purple-800 font-mono block">
                        {{ $ticket->ticketType->name ?? 'General Admission' }}
                    </span>
                    <span class="text-xs font-bold text-slate-700 block mt-1">
                        {{ $ticket->price_paid > 0 ? '₹' . number_format($ticket->price_paid) : 'Complimentary' }}
                    </span>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="flex flex-col sm:flex-row items-center justify-between p-4 bg-slate-900 text-white rounded-2xl gap-4">
                <div class="text-center sm:text-left">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-rose-400 block">Scan at Gate Turnstile</span>
                    <div class="text-xs font-mono font-bold mt-1 text-slate-200">{{ $ticket->ticket_number }}</div>
                    <div class="text-[11px] font-mono text-amber-300 mt-0.5">{{ $ticket->qr_token }}</div>
                    <div class="mt-3">
                        <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-full {{ $ticket->ticket_status === 'checked_in' ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-slate-200' }}">
                            {{ $ticket->ticket_status === 'checked_in' ? 'Admitted (' . ($ticket->checked_in_at ? $ticket->checked_in_at->format('h:i A') : '') . ')' : 'Valid for Single Entry' }}
                        </span>
                    </div>
                </div>

                <!-- Generated Visual QR Code Image -->
                <div class="bg-white p-2.5 rounded-xl shrink-0 shadow-inner">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=2&data={{ urlencode($ticket->qr_token) }}" alt="QR Pass" class="w-28 h-28 object-contain">
                </div>
            </div>

            <!-- Verification Rules Footer -->
            <div class="text-[10px] text-slate-400 border-t border-slate-100 pt-3 space-y-1">
                <p>&bull; Present this digital or printed pass at the security turnstile for barcode/QR scanner verification.</p>
                <p>&bull; Duplicate scans will be flagged by the gate controller and admission will be denied.</p>
                <p>&bull; Non-refundable and subject to organizer terms. Issued by GrandVibe Events & Productions ERP.</p>
            </div>

        </div>

    </div>

</body>
</html>

