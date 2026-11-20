<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Venue;
use App\Models\Vendor;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\EventExpense;
use App\Models\EventTask;
use App\Models\EventGuest;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEvents = Event::count();
        $activeEvents = Event::whereIn('status', ['confirmed', 'in_planning', 'active'])->count();
        $totalRevenue = Event::whereIn('status', ['confirmed', 'in_planning', 'active', 'completed'])->sum('contract_value');
        $receivedRevenue = Payment::where('status', 'completed')->sum('amount');
        $totalExpenses = EventExpense::sum('amount');
        $outstandingInvoices = Invoice::whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->sum('balance_due');
        
        $totalClients = Client::count();
        $totalLeads = Lead::count();
        $convertedLeads = Lead::where('status', 'converted')->count();
        
        $totalGuests = EventGuest::count();
        $checkedInGuests = EventGuest::where('checked_in', true)->count();

        $upcomingEvents = Event::with(['client', 'venue', 'category', 'manager'])
            ->where('start_date', '>=', Carbon::now()->subDays(1))
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get();

        $recentEvents = Event::with(['client', 'venue', 'category'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $pendingTasks = EventTask::with('event')
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date', 'asc')
            ->take(6)
            ->get();

        $eventsByCategory = Event::selectRaw('event_categories.name, count(*) as count')
            ->join('event_categories', 'events.event_category_id', '=', 'event_categories.id')
            ->groupBy('event_categories.name')
            ->get();

        return view('dashboard.index', compact(
            'totalEvents',
            'activeEvents',
            'totalRevenue',
            'receivedRevenue',
            'totalExpenses',
            'outstandingInvoices',
            'totalClients',
            'totalLeads',
            'convertedLeads',
            'totalGuests',
            'checkedInGuests',
            'upcomingEvents',
            'recentEvents',
            'pendingTasks',
            'eventsByCategory'
        ));
    }
}

