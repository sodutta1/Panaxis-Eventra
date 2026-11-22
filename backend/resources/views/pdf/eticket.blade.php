<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
  .card { border: 2px solid #105830; border-radius: 10px; padding: 0; width: 100%; }
  .top { background: #105830; color: #fff; padding: 14px 18px; }
  .top .co { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: .85; }
  .top .ev { font-size: 20px; font-weight: bold; margin-top: 2px; }
  .main { width: 100%; padding: 18px; }
  .qr { text-align: center; }
  .qr img { width: 180px; height: 180px; }
  .qr .tok { font-size: 9px; color: #666; margin-top: 4px; word-break: break-all; }
  table.det { border-collapse: collapse; width: 100%; }
  table.det td { padding: 5px 8px; vertical-align: top; }
  table.det .k { color: #666; font-size: 10px; text-transform: uppercase; width: 34%; }
  .num { font-size: 15px; font-weight: bold; color: #105830; }
  .foot { border-top: 1px dashed #999; padding: 10px 18px; font-size: 10px; color: #666; }
</style>
</head>
<body>
  <div class="card">
    <div class="top">
      <table style="width:100%; border-collapse:collapse;"><tr>
        <td>
          <div class="co">{{ $company->name }}</div>
          <div class="ev">{{ $ticket->event->title }}</div>
        </td>
        @if($logo)
        <td style="text-align:right;"><img src="{{ $logo }}" style="max-height:44px; max-width:120px;"></td>
        @endif
      </tr></table>
    </div>
    <table class="main"><tr>
      <td style="width:58%;">
        <table class="det">
          <tr><td class="k">Ticket</td><td class="num">{{ $ticket->ticket_number }}</td></tr>
          <tr><td class="k">Pass type</td><td>{{ $ticket->ticketType->name }}</td></tr>
          <tr><td class="k">Holder</td><td>{{ $ticket->holder_name }}</td></tr>
          <tr><td class="k">Date</td><td>{{ $eventStart }}</td></tr>
          @if($ticket->event->venue)
          <tr><td class="k">Venue</td><td>{{ $ticket->event->venue->name }}{{ $ticket->event->venue->city ? ', '.$ticket->event->venue->city : '' }}</td></tr>
          @endif
          <tr><td class="k">Price paid</td><td>{{ $company->currency_symbol ?? '₹' }}{{ number_format($ticket->price_paid, 2) }}</td></tr>
        </table>
      </td>
      <td class="qr">
        <img src="{{ $qr }}" alt="QR">
        <div class="tok">{{ $ticket->qr_token }}</div>
      </td>
    </tr></table>
    <div class="foot">Present this e-ticket (or the QR code) at the gate for entry. Ticket is valid for one admission and is non-transferable.</div>
  </div>
</body>
</html>
