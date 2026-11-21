<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    use ScopesToCompany;

    /** CSV check-in list for one event (guests + ticket holders). */
    public function guestList(Request $request, int $event): StreamedResponse
    {
        $model = $this->findForCompany(Event::with(['guests', 'tickets']), $request, $event);

        return $this->csv('checkin-list-'.$model->event_code.'.csv', function ($out) use ($model) {
            fputcsv($out, ['Type', 'Name', 'Email', 'Phone', 'Group/Pass', 'RSVP/Status', 'Checked in', 'Checked in at']);
            foreach ($model->guests as $g) {
                fputcsv($out, ['Guest', $g->name, $g->email, $g->phone, optional($g->group)->name, $g->rsvp_status, $g->checked_in ? 'yes' : 'no', $g->checked_in_at]);
            }
            foreach ($model->tickets as $t) {
                fputcsv($out, ['Ticket', $t->holder_name, $t->holder_email, $t->holder_phone, optional($t->ticketType)->name, $t->ticket_status, $t->ticket_status === 'checked_in' ? 'yes' : 'no', $t->checked_in_at]);
            }
        });
    }

    /** CSV of payments, optionally filtered to one event. */
    public function payments(Request $request): StreamedResponse
    {
        $query = $this->scopeCompany(Payment::with(['invoice', 'client']), $request)->orderBy('payment_date');
        if ($request->filled('event_id')) {
            $query->where('event_id', (int) $request->input('event_id'));
        }
        $payments = $query->get();

        return $this->csv('payments.csv', function ($out) use ($payments) {
            fputcsv($out, ['Payment #', 'Date', 'Invoice', 'Client', 'Method', 'Reference', 'Status', 'Amount']);
            foreach ($payments as $p) {
                fputcsv($out, [$p->payment_number, $p->payment_date, optional($p->invoice)->invoice_number, optional($p->client)->name, $p->payment_method, $p->transaction_reference, $p->status, $p->amount]);
            }
        });
    }

    private function csv(string $filename, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            $rows($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
