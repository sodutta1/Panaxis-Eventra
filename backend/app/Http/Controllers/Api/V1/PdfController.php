<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    use ScopesToCompany;

    public function invoice(Request $request, int $invoice)
    {
        $model = $this->findForCompany(Invoice::with(['items', 'payments', 'client', 'event']), $request, $invoice);
        $company = Company::findOrFail($model->company_id);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $model, 'company' => $company])->setPaper('a4');

        return $pdf->download($model->invoice_number . '.pdf');
    }

    public function quotation(Request $request, int $quotation)
    {
        $model = $this->findForCompany(Quotation::with(['items', 'client', 'event']), $request, $quotation);
        $company = Company::findOrFail($model->company_id);

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $model, 'company' => $company])->setPaper('a4');

        return $pdf->download($model->quotation_number . '.pdf');
    }
}
