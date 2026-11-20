<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PortalController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/demo-login/{id}', [AuthController::class, 'demoLogin'])->name('demo.login');

// Authenticated ERP Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Events
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status');
    Route::post('/events/{event}/schedules', [EventController::class, 'addSchedule'])->name('events.schedules.store');
    Route::post('/events/{event}/tasks', [EventController::class, 'addTask'])->name('events.tasks.store');
    Route::post('/tasks/{task}/toggle', [EventController::class, 'toggleTask'])->name('tasks.toggle');

    // CRM & Leads
    Route::resource('leads', LeadController::class);
    Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('clients', ClientController::class);

    // Venues & Vendors
    Route::resource('venues', VenueController::class);
    Route::resource('vendors', VendorController::class);

    // Proposals & Quotations
    Route::resource('quotations', QuotationController::class);
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');

    // Billing & Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');

    // Budgets & Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');

    // Inventory & Equipment
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');

    // Guests, RSVP & Check-in
    Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
    Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::post('/guests/{guest}/checkin', [GuestController::class, 'toggleCheckIn'])->name('guests.checkin');

    // Ticketing & QR Scanner
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/types', [TicketController::class, 'storeType'])->name('tickets.types.store');
    Route::post('/tickets/verify', [TicketController::class, 'verifyTicket'])->name('tickets.verify');
    Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');

    // Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Portals
    Route::get('/portal/client', [PortalController::class, 'clientPortal'])->name('portal.client');
    Route::get('/portal/vendor', [PortalController::class, 'vendorPortal'])->name('portal.vendor');
});
