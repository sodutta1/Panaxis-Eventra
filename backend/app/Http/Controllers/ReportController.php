<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Payment;
use App\Models\EventExpense;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\EventGuest;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $revenueByCategory = DB::table('events')
            ->join('event_categories', 'events.event_category_id', '=', 'event_categories.id')
            ->select('event_categories.name', DB::raw('SUM(events.contract_value) as total_contract'), DB::raw('SUM(events.amount_received) as total_received'), DB::raw('count(*) as event_count'))
            ->groupBy('event_categories.name')
            ->get();

        $monthlyEvents = DB::table('events')
            ->select(DB::raw("DATE_FORMAT(start_date, '%b %Y') as month"), DB::raw('COUNT(*) as total_events'), DB::raw('SUM(contract_value) as total_revenue'))
            ->groupBy('month')
            ->orderByRaw('MIN(start_date) ASC')
            ->take(12)
            ->get();

        $topClients = Client::withSum('events', 'contract_value')
            ->withCount('events')
            ->orderBy('events_sum_contract_value', 'desc')
            ->take(5)
            ->get();

        $topVendors = Vendor::withSum('expenses', 'amount')
            ->withCount('assignments')
            ->orderBy('expenses_sum_amount', 'desc')
            ->take(5)
            ->get();

        $totalRevenue = Event::sum('contract_value');
        $totalCost = EventExpense::sum('amount');
        $grossProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;

        return view('reports.index', compact(
            'revenueByCategory',
            'monthlyEvents',
            'topClients',
            'topVendors',
            'totalRevenue',
            'totalCost',
            'grossProfit',
            'profitMargin'
        ));
    }
}

