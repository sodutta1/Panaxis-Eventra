<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
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
                Print Tax Invoice
            </button>
        </div>

        <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
            <div>
                <span class="text-[10px] font-bold tracking-widest uppercase bg-slate-900 text-white px-2 py-0.5 rounded">TAX INVOICE</span>
                <h1 class="text-2xl font-black text-slate-900 mt-2">GrandVibe Events & Productions</h1>
                <p class="text-xs text-slate-500 mt-1">Floor 12, Crescenzo Tower, Bandra Kurla Complex, Mumbai 400051</p>
                <p class="text-xs text-slate-500">GSTIN: 27AAACG1234F1Z5 | PAN: AAACG1234F</p>
            </div>
            <div class="text-right">
                <span class="text-xl font-bold text-slate-900 font-mono">{{ $invoice->invoice_number }}</span>
                <p class="text-xs text-slate-500 mt-1">Invoice Date: {{ date('d M Y', strtotime($invoice->invoice_date)) }}</p>
                <p class="text-xs text-slate-500 font-semibold text-rose-600">Due Date: {{ date('d M Y', strtotime($invoice->due_date)) }}</p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="font-bold uppercase text-slate-400 block mb-1">Billed To:</span>
                <h3 class="font-bold text-sm text-slate-900">{{ $invoice->client->name }}</h3>
                <p class="text-slate-600">{{ $invoice->client->company_name ?? '' }}</p>
                <p class="text-slate-600">{{ $invoice->client->address ?? '' }}</p>
                <p class="text-slate-600">GSTIN: {{ $invoice->client->tax_number ?? 'URP' }}</p>
            </div>
            @if ($invoice->event)
                <div class="text-right">
                    <span class="font-bold uppercase text-slate-400 block mb-1">Production Project:</span>
                    <h3 class="font-bold text-sm text-slate-900">{{ $invoice->event->title }}</h3>
                    <p class="text-slate-600 font-mono">{{ $invoice->event->event_code }}</p>
                </div>
            @endif
        </div>

        <table class="w-full text-xs text-left mb-6">
            <thead class="bg-slate-100 uppercase font-bold text-slate-600">
                <tr>
                    <th class="py-2 px-3">Description</th>
                    <th class="py-2 px-3 text-center">Qty</th>
                    <th class="py-2 px-3 text-right">Unit Rate (₹)</th>
                    <th class="py-2 px-3 text-right">Taxable Amount (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $item->description }}</td>
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
                    <span class="font-bold">₹{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>CGST ({{ $invoice->tax_rate / 2 }}%):</span>
                    <span>₹{{ number_format($invoice->tax_amount / 2, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>SGST ({{ $invoice->tax_rate / 2 }}%):</span>
                    <span>₹{{ number_format($invoice->tax_amount / 2, 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-300 flex justify-between font-black text-sm text-slate-900">
                    <span>Invoice Total:</span>
                    <span>₹{{ number_format($invoice->grand_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-600 font-bold">
                    <span>Amount Paid:</span>
                    <span>-₹{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-black text-base text-rose-600 pt-1 border-t border-slate-200">
                    <span>Balance Due:</span>
                    <span>₹{{ number_format($invoice->balance_due, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="p-4 bg-slate-50 rounded-lg text-slate-600 text-xs border border-slate-200 mb-6">
            <strong>Bank Coordinates for RTGS / NEFT:</strong><br>
            Bank Name: HDFC Bank Ltd | Branch: Bandra Kurla Complex, Mumbai<br>
            Account Name: GrandVibe Events & Productions Pvt. Ltd.<br>
            Account No: 50200012345678 | IFSC: HDFC0000060
        </div>

        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 text-xs text-slate-500">
            <div>Authorized Signatory<br>GrandVibe Events & Productions Pvt. Ltd.</div>
            <div class="text-right">Thank you for your business!</div>
        </div>
    </div>

</body>
</html>

