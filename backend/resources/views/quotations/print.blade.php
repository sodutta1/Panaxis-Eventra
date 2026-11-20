<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-8 text-slate-800 font-sans">

    <div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-slate-200">
        <div class="no-print mb-4 flex justify-end">
            <button onclick="window.print()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow">
                Print Quotation
            </button>
        </div>

        <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900">GrandVibe Events & Productions</h1>
                <p class="text-xs text-slate-500 mt-1">Floor 12, Crescenzo Tower, Bandra Kurla Complex, Mumbai 400051</p>
                <p class="text-xs text-slate-500">Phone: +91 98200 12345 | Email: contact@grandvibe-events.com</p>
                <p class="text-xs font-semibold text-slate-600 mt-1">GSTIN: 27AAACG1234F1Z5</p>
            </div>
            <div class="text-right">
                <span class="text-xl font-bold text-rose-600 font-mono">{{ $quotation->quotation_number }}</span>
                <p class="text-xs text-slate-500 mt-1">Date: {{ date('d M Y', strtotime($quotation->issue_date)) }}</p>
                <p class="text-xs text-slate-500">Valid Until: {{ date('d M Y', strtotime($quotation->valid_until)) }}</p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="font-bold uppercase text-slate-400 block mb-1">To:</span>
                <h3 class="font-bold text-sm text-slate-900">{{ $quotation->client->name }}</h3>
                <p class="text-slate-600">{{ $quotation->client->company_name ?? '' }}</p>
                <p class="text-slate-600">{{ $quotation->client->address ?? '' }}</p>
                <p class="text-slate-600">Phone: {{ $quotation->client->phone }}</p>
            </div>
            @if ($quotation->event)
                <div class="text-right">
                    <span class="font-bold uppercase text-slate-400 block mb-1">Event:</span>
                    <h3 class="font-bold text-sm text-slate-900">{{ $quotation->event->title }}</h3>
                    <p class="text-slate-600 font-mono">{{ $quotation->event->event_code }}</p>
                </div>
            @endif
        </div>

        <table class="w-full text-xs text-left mb-6">
            <thead class="bg-slate-100 uppercase font-bold text-slate-600">
                <tr>
                    <th class="py-2 px-3">Item</th>
                    <th class="py-2 px-3 text-center">Qty</th>
                    <th class="py-2 px-3 text-right">Rate (₹)</th>
                    <th class="py-2 px-3 text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($quotation->items as $item)
                    <tr>
                        <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $item->item_name }}</td>
                        <td class="py-2.5 px-3 text-center">{{ $item->quantity }}</td>
                        <td class="py-2.5 px-3 text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end mb-6 text-xs">
            <div class="w-64 space-y-1.5">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-bold">₹{{ number_format($quotation->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>GST ({{ $quotation->tax_rate }}%):</span>
                    <span class="font-bold">₹{{ number_format($quotation->tax_amount, 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-300 flex justify-between font-black text-sm text-slate-900">
                    <span>Total Amount:</span>
                    <span class="text-rose-600">₹{{ number_format($quotation->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        @if ($quotation->notes)
            <div class="p-4 bg-slate-50 rounded-lg text-slate-600 text-xs border border-slate-200 mb-6">
                <strong>Terms & Notes:</strong> {{ $quotation->notes }}
            </div>
        @endif

        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 text-xs text-slate-500">
            <div>Authorized Signatory<br>GrandVibe Events & Productions</div>
            <div class="text-right">Client Acceptance & Signature<br>Date: _______________</div>
        </div>
    </div>

</body>
</html>

