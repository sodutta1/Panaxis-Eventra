<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\TaskResource;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventGuest;
use App\Models\EventTask;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesToCompany;

    /**
     * Dashboard summary - mirrors DashboardController@index from the web app.
     * GET /api/v1/dashboard
     */
    public function index(Request $request)
    {
        $companyId = $this->companyId($request);
        $company = fn ($query) => $query->where('company_id', $companyId);

        return response()->json([
            'data' => [
                'total_events' => $company(Event::query())->count(),
                'active_events' => $company(Event::query())->whereIn('status', ['confirmed', 'in_planning', 'active'])->count(),
                'total_revenue' => $company(Event::query())->whereIn('status', ['confirmed', 'in_planning', 'active', 'completed'])->sum('contract_value'),
                'received_revenue' => $company(Payment::query())->where('status', 'completed')->sum('amount'),
                'total_expenses' => $company(EventExpense::query())->sum('amount'),
                'outstanding_invoices' => $company(Invoice::query())->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->sum('balance_due'),
                'total_clients' => $company(Client::query())->count(),
                'total_leads' => $company(Lead::query())->count(),
                'converted_leads' => $company(Lead::query())->where('status', 'converted')->count(),
                'total_guests' => $company(EventGuest::query())->count(),
                'checked_in_guests' => $company(EventGuest::query())->where('checked_in', true)->count(),
                'upcoming_events' => EventResource::collection(
                    $company(Event::with(['client', 'venue', 'category', 'manager']))
                        ->where('start_date', '>=', Carbon::now()->subDays(1))
                        ->orderBy('start_date', 'asc')->take(6)->get()
                ),
                'pending_tasks' => TaskResource::collection(
                    EventTask::with('assignedUser')
                        ->whereIn('status', ['pending', 'in_progress'])
                        ->whereHas('event', fn ($q) => $q->where('company_id', $companyId))
                        ->orderBy('due_date', 'asc')->take(6)->get()
                ),
                'events_by_category' => Event::query()
                    ->where('events.company_id', $companyId)
                    ->selectRaw('event_categories.name, count(*) as count')
                    ->join('event_categories', 'events.event_category_id', '=', 'event_categories.id')
                    ->groupBy('event_categories.name')->get(),
            ],
        ]);
    }
}
