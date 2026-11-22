<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\VendorAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    use ScopesToCompany;

    public function invoice(Request $request, int $invoice)
    {
        $model = $this->findForCompany(Invoice::with(['items', 'payments', 'client', 'event']), $request, $invoice);
        $company = Company::findOrFail($model->company_id);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $model, 'company' => $company, 'logo' => $this->logoDataUri($company)])->setPaper('a4');

        return $pdf->download($model->invoice_number.'.pdf');
    }

    public function quotation(Request $request, int $quotation)
    {
        $model = $this->findForCompany(Quotation::with(['items', 'client', 'event']), $request, $quotation);
        $company = Company::findOrFail($model->company_id);

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $model, 'company' => $company, 'logo' => $this->logoDataUri($company)])->setPaper('a4');

        return $pdf->download($model->quotation_number.'.pdf');
    }

    /** Staff: e-ticket PDF with QR for a ticket in their company. */
    public function ticketPdf(Request $request, int $ticket)
    {
        $model = $this->findForCompany(Ticket::with(['event.venue', 'ticketType']), $request, $ticket);

        return $this->eticketResponse($model);
    }

    /** Public: e-ticket PDF addressed by the unguessable qr_token. */
    public function eticket(string $qrToken)
    {
        $model = Ticket::with(['event.venue', 'ticketType'])
            ->where('qr_token', $qrToken)
            ->firstOrFail();

        return $this->eticketResponse($model);
    }

    protected function eticketResponse(Ticket $ticket)
    {
        $company = Company::findOrFail($ticket->company_id);
        $qr = (new QRCode(new QROptions(['outputType' => QRGdImagePNG::class, 'scale' => 6])))
            ->render($ticket->qr_token);

        $pdf = Pdf::loadView('pdf.eticket', [
            'ticket' => $ticket,
            'company' => $company,
            'qr' => $qr,
            'logo' => $this->logoDataUri($company),
            'eventStart' => Carbon::parse($ticket->event->getRawOriginal('start_date'), 'Asia/Kolkata')->format('d M Y, h:i A'),
        ]);

        return $pdf->download('eticket-'.$ticket->ticket_number.'.pdf');
    }

    /** Staff: PO-style vendor work order PDF with scope and payment terms. */
    public function workOrder(Request $request, int $workOrder)
    {
        $model = $this->findForCompany(VendorAssignment::with(['vendor.category', 'event.venue']), $request, $workOrder);
        $company = Company::findOrFail($model->company_id);

        $pdf = Pdf::loadView('pdf.work_order', [
            'workOrder' => $model,
            'company' => $company,
            'logo' => $this->logoDataUri($company),
        ])->setPaper('a4');

        return $pdf->download('work-order-'.$model->id.'.pdf');
    }

    /** Staff: post-event closeout report - attendance, revenue, vendor costs, balances. */
    public function closeout(Request $request, int $event)
    {
        $model = $this->findForCompany(Event::with(['venue', 'guests', 'tickets']), $request, $event);
        $company = Company::findOrFail($model->company_id);

        $guests = $model->guests;
        $tickets = $model->tickets->where('payment_status', '!=', 'refunded');

        $invoices = Invoice::with('payments')->where('company_id', $model->company_id)->where('event_id', $model->id)->get();
        $invoiced = $invoices->sum('grand_total');
        $received = $invoices->sum('paid_amount');

        $workOrders = VendorAssignment::with('vendor')->where('company_id', $model->company_id)->where('event_id', $model->id)->get();
        $vendorTotals = [
            'contract' => $workOrders->sum('contract_amount'),
            'paid' => $workOrders->sum('paid_amount'),
        ];
        $vendorTotals['balance'] = max(0, $vendorTotals['contract'] - $vendorTotals['paid']);

        $pdf = Pdf::loadView('pdf.closeout', [
            'event' => $model,
            'company' => $company,
            'logo' => $this->logoDataUri($company),
            'cur' => $company->currency_symbol ?? '₹',
            'attendance' => [
                'guests_total' => $guests->count(),
                'guests_checked_in' => $guests->where('checked_in', true)->count(),
                'tickets_sold' => $tickets->count(),
                'tickets_scanned' => $tickets->where('ticket_status', 'checked_in')->count(),
            ],
            'revenue' => [
                'ticket_sales' => $tickets->sum('price_paid'),
                'invoiced' => $invoiced,
                'received' => $received,
                'outstanding' => max(0, $invoiced - $received),
            ],
            'vendorCosts' => $workOrders,
            'vendorTotals' => $vendorTotals,
        ])->setPaper('a4');

        return $pdf->download('closeout-'.$model->event_code.'.pdf');
    }

    /** Embed the company logo as a data URI so dompdf needs no file/remote access. */
    protected function logoDataUri(Company $company): ?string
    {
        if (! $company->logo_path || ! Storage::disk('public')->exists($company->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($company->logo_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($company->logo_path));
    }
}
