<?php

namespace Tests\Feature\Api\V1;

use App\Models\Company;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\VendorAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@eventflow.test', 'password' => 'password',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_eticket_pdf_downloads_for_staff_and_publicly_by_qr_token(): void
    {
        $auth = $this->admin();
        $ticket = Ticket::firstOrFail();

        $this->get('/api/v1/tickets/'.$ticket->id.'/pdf', $auth)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get('/api/v1/public/tickets/'.$ticket->qr_token.'/eticket')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get('/api/v1/public/tickets/QR-does-not-exist/eticket')->assertNotFound();
    }

    public function test_work_order_pdf_downloads(): void
    {
        $auth = $this->admin();
        $wo = VendorAssignment::firstOrFail();

        $this->get('/api/v1/work-orders/'.$wo->id.'/pdf', $auth)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_closeout_pdf_downloads(): void
    {
        $auth = $this->admin();
        $event = Event::firstOrFail();

        $this->get('/api/v1/events/'.$event->id.'/closeout-pdf', $auth)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_list_csv_export(): void
    {
        $auth = $this->admin();
        $event = Event::has('guests')->firstOrFail();

        $res = $this->get('/api/v1/events/'.$event->id.'/guests/export', $auth)->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
        $this->assertStringContainsString('Checked in', $res->streamedContent());
    }

    public function test_payments_csv_export(): void
    {
        $auth = $this->admin();

        $res = $this->get('/api/v1/payments/export', $auth)->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
        $this->assertStringContainsString('Payment #', $res->streamedContent());
    }

    public function test_company_logo_upload_and_branded_quotation_pdf(): void
    {
        Storage::fake('public');
        $auth = $this->admin();

        $this->postJson('/api/v1/admin/branding/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ], $auth)->assertOk()->assertJsonPath('data.logo_path', fn ($p) => str_starts_with($p, 'branding/'));

        $company = Company::firstOrFail();
        $this->assertNotNull($company->fresh()->logo_path);
        $this->assertNotNull($company->fresh()->logo_url);

        $quotation = Quotation::firstOrFail();
        $this->get('/api/v1/quotations/'.$quotation->id.'/pdf', $auth)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $invoice = Invoice::firstOrFail();
        $this->get('/api/v1/invoices/'.$invoice->id.'/pdf', $auth)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
