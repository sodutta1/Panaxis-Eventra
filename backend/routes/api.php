<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\PortalController;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
| Versioned REST API added on top of the existing session-based Blade app.
| Auth: Sanctum personal access tokens (Authorization: Bearer <token>).
| Tenancy: every query is scoped to the authenticated user's company_id.
| RBAC: the `role` middleware gates writes per staff role.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public: issue a Sanctum token
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Staff area - portal-only roles (client, vendor) are excluded here
        $staff = 'role:super_admin,company_admin,admin,event_manager,sales_executive,accountant,staff';
        $admins = 'role:super_admin,company_admin,admin';
        $finance = 'role:super_admin,company_admin,admin,accountant';
        $sales = 'role:super_admin,company_admin,admin,event_manager,sales_executive';
        $ops = 'role:super_admin,company_admin,admin,event_manager,staff';

        Route::middleware($staff)->group(function () use ($admins, $finance, $sales, $ops) {

            // Dashboard / Reports
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // CRM: Leads
            Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
            Route::post('/leads', [LeadController::class, 'store'])->name('leads.store')->middleware($sales);
            Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
            Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update')->middleware($sales);
            Route::patch('/leads/{lead}', [LeadController::class, 'update'])->middleware($sales);
            Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy')->middleware($admins);
            Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status')->middleware($sales);
            Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert')->middleware($sales);

            // Clients
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store')->middleware($sales);
            Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
            Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update')->middleware($sales);
            Route::patch('/clients/{client}', [ClientController::class, 'update'])->middleware($sales);
            Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy')->middleware($admins);

            // Events + Run of Show + Tasks
            Route::get('/events', [EventController::class, 'index'])->name('events.index');
            Route::post('/events', [EventController::class, 'store'])->name('events.store')->middleware($ops . ',event_manager');
            Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update')->middleware($ops);
            Route::patch('/events/{event}', [EventController::class, 'update'])->middleware($ops);
            Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy')->middleware($admins);
            Route::post('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status')->middleware($ops);
            Route::post('/events/{event}/schedules', [EventController::class, 'addSchedule'])->name('events.schedules.store')->middleware($ops);
            Route::post('/events/{event}/tasks', [EventController::class, 'addTask'])->name('events.tasks.store')->middleware($ops);
            Route::post('/tasks/{task}/toggle', [EventController::class, 'toggleTask'])->name('tasks.toggle')->middleware($ops);

            // Venues
            Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
            Route::post('/venues', [VenueController::class, 'store'])->name('venues.store')->middleware($admins);
            Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
            Route::put('/venues/{venue}', [VenueController::class, 'update'])->name('venues.update')->middleware($admins);
            Route::patch('/venues/{venue}', [VenueController::class, 'update'])->middleware($admins);
            Route::delete('/venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy')->middleware($admins);

            // Vendors
            Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
            Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store')->middleware($ops);
            Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
            Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update')->middleware($ops);
            Route::patch('/vendors/{vendor}', [VendorController::class, 'update'])->middleware($ops);
            Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy')->middleware($admins);

            // Quotations
            Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
            Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store')->middleware($sales);
            Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
            Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy')->middleware($admins);

            // Invoices & Payments (finance)
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware($finance);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware($admins);
            Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store')->middleware($finance);

            // Expenses (finance)
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware($finance);
            Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware($finance);

            // Guests & RSVP
            Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
            Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
            Route::post('/guests', [GuestController::class, 'store'])->name('guests.store')->middleware($ops);
            Route::post('/guests/{guest}/checkin', [GuestController::class, 'toggleCheckIn'])->name('guests.checkin')->middleware($ops);

            // Tickets & Gate Scan
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store')->middleware($ops);
            Route::post('/tickets/types', [TicketController::class, 'storeType'])->name('tickets.types.store')->middleware($ops);
            Route::post('/tickets/verify', [TicketController::class, 'verifyTicket'])->name('tickets.verify')->middleware('throttle:30,1');
        });

        // Portals (token holders with client/vendor roles + admins)
        Route::get('/portal/client', [PortalController::class, 'clientPortal'])
            ->name('portal.client')
            ->middleware('role:client,super_admin,company_admin,admin');
        Route::get('/portal/vendor', [PortalController::class, 'vendorPortal'])
            ->name('portal.vendor')
            ->middleware('role:vendor,super_admin,company_admin,admin');
    });
});
