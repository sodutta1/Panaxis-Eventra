<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { overflow-wrap: break-word; word-wrap: break-word; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
  .head { width: 100%; margin-bottom: 18px; }
  .brand { color: #22543d; font-size: 20px; font-weight: bold; }
  .muted { color: #666; }
  table.items { table-layout: fixed; width: 100%; border-collapse: collapse; margin-top: 14px; }
  table.items th { background: #22543d; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
  table.items td { border-bottom: 1px solid #ddd; padding: 6px 8px; }
  .right { text-align: right; }
  .totals { width: 45%; margin-left: 55%; margin-top: 12px; border-collapse: collapse; }
  .totals td { padding: 4px 8px; }
  .totals .grand td { border-top: 2px solid #22543d; font-weight: bold; font-size: 13px; color: #22543d; background: #f1f6f1; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; background: #dce9df; color: #163a2b; }
  .foot { margin-top: 26px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
</style>
</head>
<body>
  <table class="head"><tr>
    <td>
      @if(!empty($logo))<img src="{{ $logo }}" style="max-height:42px; max-width:150px; margin-bottom:4px;"><br>@endif
      <div class="brand">{{ $company->name }}</div>
      <div class="muted">{{ $company->address }}, {{ $company->city }}, {{ $company->state }}</div>
      <div class="muted">{{ $company->email }} · {{ $company->phone }}</div>
      <div class="muted">GSTIN: {{ $company->tax_number }}</div>
    </td>
    <td class="right">
      <div style="font-size:16px;font-weight:bold;">TAX INVOICE</div>
      <div>{{ $invoice->invoice_number }}</div>
      <div class="muted">Date: {{ $invoice->invoice_date }}</div>
      <div class="muted">Due: {{ $invoice->due_date }}</div>
      <div class="badge">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</div>
    </td>
  </tr></table>

  <table class="head"><tr>
    <td>
      <div class="muted" style="font-size:10px;text-transform:uppercase;">Billed to</div>
      <div style="font-weight:bold;">{{ $invoice->client->name }}</div>
      <div>{{ $invoice->client->company_name }}</div>
      <div class="muted">{{ $invoice->client->address }} {{ $invoice->client->city }} {{ $invoice->client->state }}</div>
      @if($invoice->client->tax_number)<div class="muted">GSTIN: {{ $invoice->client->tax_number }}</div>@endif
    </td>
    @if($invoice->event)
    <td class="right">
      <div class="muted" style="font-size:10px;text-transform:uppercase;">Event</div>
      <div style="font-weight:bold;">{{ $invoice->event->title }}</div>
      <div class="muted">{{ $invoice->event->event_code }}</div>
    </td>
    @endif
  </tr></table>

  <table class="items">
    <thead><tr><th style="width:5%">#</th><th style="width:55%">Description</th><th style="width:10%" class="right">Qty</th><th style="width:15%" class="right">Unit price</th><th style="width:15%" class="right">Amount</th></tr></thead>
    <tbody>
      @foreach($invoice->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $item->description }}</td>
        <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
        <td class="right">₹{{ number_format($item->unit_price, 2) }}</td>
        <td class="right">₹{{ number_format($item->total_price, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotal</td><td class="right">₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
    @if((float)$invoice->discount > 0)<tr><td>Discount</td><td class="right">-₹{{ number_format($invoice->discount, 2) }}</td></tr>@endif
    <tr><td>GST ({{ rtrim(rtrim(number_format($invoice->tax_rate, 2), '0'), '.') }}%)</td><td class="right">₹{{ number_format($invoice->tax_amount, 2) }}</td></tr>
    <tr class="grand"><td>Grand total</td><td class="right">₹{{ number_format($invoice->grand_total, 2) }}</td></tr>
    <tr><td>Paid</td><td class="right">₹{{ number_format($invoice->paid_amount, 2) }}</td></tr>
    <tr><td style="font-weight:bold;">Balance due</td><td class="right" style="font-weight:bold;">₹{{ number_format($invoice->balance_due, 2) }}</td></tr>
  </table>

  @if($invoice->payments->count())
  <table class="items">
    <thead><tr><th>Payment #</th><th>Date</th><th>Method</th><th>Reference</th><th class="right">Amount</th></tr></thead>
    <tbody>
      @foreach($invoice->payments as $p)
      <tr>
        <td>{{ $p->payment_number }}</td>
        <td>{{ $p->payment_date }}</td>
        <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
        <td>{{ $p->transaction_reference }}</td>
        <td class="right">₹{{ number_format($p->amount, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  @if($invoice->notes)<div class="foot"><strong>Notes:</strong> {{ $invoice->notes }}</div>@endif
  <div class="foot">Generated by Panaxis Eventra · {{ now()->format('d M Y, H:i') }}</div>
</body>
</html>
