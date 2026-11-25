<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PdfController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\PublicBookingController;
use App\Http\Controllers\Api\V1\QuotationController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:'.(app()->environment('production') ? '5,1' : '120,1'));

    // Public booking page (no auth; Razorpay TEST MODE)
    Route::get('/public/events/{event}', [PublicBookingController::class, 'show'])->name('public.events.show');
    Route::post('/public/events/{event}/book', [PublicBookingController::class, 'book'])->name('public.events.book')->middleware('throttle:20,1');
    Route::post('/public/bookings/{booking}/simulate-payment', [PublicBookingController::class, 'simulatePayment'])->name('public.bookings.simulate')->middleware('throttle:20,1');
    Route::post('/public/bookings/{booking}/confirm', [PublicBookingController::class, 'confirm'])->name('public.bookings.confirm')->middleware('throttle:20,1');
    Route::get('/public/tickets/{qrToken}/eticket', [PdfController::class, 'eticket'])->name('public.tickets.eticket')->middleware('throttle:30,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Notification centre: each user sees only their own feed
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        // Staff area - portal-only roles (client, vendor) are excluded here
        $staff = 'role:super_admin,company_admin,admin,event_manager,sales_executive,accountant,staff';
        $admins = 'role:super_admin,company_admin,admin';
        $finance = 'role:super_admin,company_admin,admin,accountant';
        $sales = 'role:super_admin,company_admin,admin,event_manager,sales_executive';
        $ops = 'role:super_admin,company_admin,admin,event_manager,staff';

        Route::middleware($staff)->group(function () use ($admins, $finance, $sales, $ops) {

            Route::get('/quick-find', \App\Http\Controllers\Api\V1\QuickFindController::class);

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
            Route::post('/events', [EventController::class, 'store'])->name('events.store')->middleware($ops.',event_manager');
            Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update')->middleware($ops);
            Route::patch('/events/{event}', [EventController::class, 'update'])->middleware($ops);
            Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy')->middleware($admins);
            Route::post('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status')->middleware($ops);
            Route::post('/events/{event}/schedules', [EventController::class, 'addSchedule'])->name('events.schedules.store')->middleware($ops);
            Route::get('/events/{event}/tasks', [EventController::class, 'tasksIndex'])->name('events.tasks.index');
            Route::post('/events/{event}/tasks', [EventController::class, 'addTask'])->name('events.tasks.store')->middleware($ops);
            Route::put('/tasks/{task}', [EventController::class, 'updateTask'])->name('tasks.update')->middleware($ops);
            Route::patch('/tasks/{task}', [EventController::class, 'updateTask'])->middleware($ops);
            Route::post('/tasks/{task}/toggle', [EventController::class, 'toggleTask'])->name('tasks.toggle')->middleware($ops);

            Route::get('/events/{event}/stream/{section}', [EventController::class,'stream']);

            // Analytics dashboard
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

            // Venues
            Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
            Route::post('/venues', [VenueController::class, 'store'])->name('venues.store')->middleware($admins);
            Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
            Route::put('/venues/{venue}', [VenueController::class, 'update'])->name('venues.update')->middleware($admins);
            Route::patch('/venues/{venue}', [VenueController::class, 'update'])->middleware($admins);
            Route::delete('/venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy')->middleware($admins);

            // Vendors
            Route::get('/vendor-categories', [VendorController::class, 'categories'])->name('vendor-categories.index');
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
            Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update')->middleware($admins);
            Route::patch('/quotations/{quotation}', [QuotationController::class, 'update'])->middleware($admins);
            Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy')->middleware($admins);

            // Invoices & Payments (finance)
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware($finance);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware($admins);
            Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store')->middleware($finance);

            // Expenses (finance)
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/expenses/{expense}/bill', [ExpenseController::class,'bill']);
            Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware($finance);
            Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware($finance);
            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware($finance);
            Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->middleware($finance);
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware($admins);

            Route::post('/expenses/{expense}/reverse', [ExpenseController::class, 'reverse'])->middleware($finance);

            Route::get('/events/{event}/budget/items', [BudgetController::class,'items']);

            // Budgets
            Route::get('/events/{event}/budget', [BudgetController::class, 'show'])->name('budgets.show');
            Route::post('/events/{event}/budget/items', [BudgetController::class, 'addItem'])->name('budgets.items.store')->middleware($ops);
            Route::patch('/budget-items/{item}', [BudgetController::class, 'updateItem'])->name('budgets.items.update')->middleware($finance);

            Route::get('/work-orders/{workOrder}', [WorkOrderController::class,'show']);

            // Vendor work orders
            Route::get('/events/{event}/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
            Route::post('/events/{event}/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store')->middleware($ops);
            Route::post('/work-orders/{workOrder}/payment', [WorkOrderController::class, 'addPayment'])->name('work-orders.payment')->middleware($finance);
            Route::post('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.status')->middleware($ops);

            // PDF documents
            Route::get('/invoices/{invoice}/pdf', [PdfController::class, 'invoice'])->name('invoices.pdf');
            Route::get('/quotations/{quotation}/pdf', [PdfController::class, 'quotation'])->name('quotations.pdf');
            Route::get('/tickets/{ticket}/pdf', [PdfController::class, 'ticketPdf'])->name('tickets.pdf');
            Route::get('/work-orders/{workOrder}/pdf', [PdfController::class, 'workOrder'])->name('work-orders.pdf');
            Route::get('/events/{event}/closeout-pdf', [PdfController::class, 'closeout'])->name('events.closeout-pdf')->middleware($finance);

            // CSV exports
            Route::get('/events/{event}/guests/export', [ExportController::class, 'guestList'])->name('events.guests.export');
            Route::get('/payments/export', [ExportController::class, 'payments'])->name('payments.export')->middleware($finance);

            // Admin: tenant user management
            Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users')->middleware($admins);
            Route::post('/admin/users', [AdminController::class, 'createUser'])->name('admin.users.store')->middleware($admins);
            Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update')->middleware($admins);
            Route::post('/admin/branding/logo', [AdminController::class, 'uploadLogo'])->name('admin.branding.logo')->middleware($admins);

            // Guests & RSVP
            Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
            Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
            Route::post('/guests', [GuestController::class, 'store'])->name('guests.store')->middleware($ops);
            Route::post('/guests/{guest}/checkin', [GuestController::class, 'toggleCheckIn'])->name('guests.checkin')->middleware($ops);
            Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update')->middleware($ops);
            Route::patch('/guests/{guest}', [GuestController::class, 'update'])->middleware($ops);
            Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy')->middleware($admins);

            // Tickets & Gate Scan
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/types', [TicketController::class, 'types'])->name('tickets.types.index');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store')->middleware($ops);
            Route::post('/tickets/types', [TicketController::class, 'storeType'])->name('tickets.types.store')->middleware($ops);
            Route::post('/tickets/verify', [TicketController::class, 'verifyTicket'])->name('tickets.verify')->middleware('throttle:30,1');
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update')->middleware($ops);
            Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->middleware($ops);
            Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy')->middleware($admins);
        });

            Route::get('/documents/{type}/{id}/record', [\App\Http\Controllers\Api\V1\DocumentsController::class,'show']);
            Route::get('/documents/{type}/{id}/link', [\App\Http\Controllers\Api\V1\DocumentsController::class,'link']);
            Route::get('/documents/{type}/{id}/download', [\App\Http\Controllers\Api\V1\DocumentsController::class,'download'])->name('documents.download');
            Route::post('/documents/{type}/bulk', [\App\Http\Controllers\Api\V1\DocumentsController::class,'bulk']);


        Route::get('/portal/{kind}/records', [PortalController::class,'records']);
        // Portals (token holders with client/vendor roles + admins)
        Route::get('/portal/client', [PortalController::class, 'clientPortal'])
            ->name('portal.client')
            ->middleware('role:client,super_admin,company_admin,admin');
        Route::get('/portal/vendor', [PortalController::class, 'vendorPortal'])
            ->name('portal.vendor')
            ->middleware('role:vendor,super_admin,company_admin,admin');
    });
});
