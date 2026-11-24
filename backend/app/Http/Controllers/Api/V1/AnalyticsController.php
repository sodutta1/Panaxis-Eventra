<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventTask;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\VendorAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Unified analytics dashboard: KPI cards, task completion, idle/overdue
 * tasks by event category, leads funnel, top cost categories, vendor burn
 * rate, and repeat vs churned clients. Every figure is company-scoped.
 */
class AnalyticsController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $companyId = $this->companyId($request);
        $co = fn ($query) => $query->where('company_id', $companyId);
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $tasks = EventTask::whereHas('event', fn ($q) => $q->where('company_id', $companyId))->get();
        $done = $tasks->where('status', 'completed')->count();
        $total = $tasks->count();

        $budgetEstimated = $co(Event::query())->whereIn('status', ['confirmed', 'in_planning', 'in_progress', 'active'])->sum('estimated_budget');
        $budgetActual = $co(Event::query())->whereIn('status', ['confirmed', 'in_planning', 'in_progress', 'active'])->sum('actual_budget');

        $tasksByCategory = fn ($collection) => EventTask::query()
            ->whereIn('event_tasks.id', $collection->pluck('id')->whenEmpty(fn ($c) => $c->push(0)))
            ->join('events', 'event_tasks.event_id', '=', 'events.id')
            ->leftJoin('event_categories', 'events.event_category_id', '=', 'event_categories.id')
            ->selectRaw('COALESCE(event_categories.name, \'Uncategorised\') as name, count(*) as count')
            ->groupBy('name')->orderByDesc('count')->limit(5)->get();

        $idleTasks = $tasks->whereIn('status', ['pending'])->where(fn ($t) => ! $t->due_date || $t->due_date >= now()->toDateString());
        $overdueTasks = $tasks->whereIn('status', ['pending', 'in_progress'])->where('due_date', '<', now()->toDateString());

        $vendorPayments = VendorAssignment::where('vendor_assignments.company_id', $companyId)
            ->where('paid_amount', '>', 0)
            ->where('updated_at', '>=', Carbon::now()->subWeeks(4)->startOfWeek())
            ->selectRaw('YEARWEEK(updated_at, 1) as yw, MIN(DATE(updated_at)) as week_label, SUM(paid_amount) as total')
            ->groupBy('yw')->orderBy('yw')->get();

        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $clientIds = $co(Client::query())->pluck('id');
        $eventCountsByClient = Event::where('company_id', $companyId)->whereIn('client_id', $clientIds)
            ->selectRaw('client_id, count(*) as c, MAX(start_date) as last_event')
            ->groupBy('client_id')->get()->keyBy('client_id');
        $repeat = $eventCountsByClient->where('c', '>', 1)->count();
        $churned = $eventCountsByClient->filter(fn ($r) => $r->last_event && Carbon::parse($r->last_event)->lt($sixMonthsAgo))->count();
        $new = $clientIds->count() - $eventCountsByClient->count();

        return response()->json(['data' => [
            'kpis' => [
                'events_this_week' => $co(Event::query())->whereBetween('start_date', [$weekStart, $weekEnd])->count(),
                'open_tasks' => $tasks->whereIn('status', ['pending', 'in_progress'])->count(),
                'budget_utilization' => $budgetEstimated > 0 ? round($budgetActual / $budgetEstimated * 100, 1) : 0,
                'bills_due' => $co(Invoice::query())->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->sum('balance_due'),
                'received_revenue' => $co(Payment::query())->where('status', 'completed')->sum('amount'),
            ],
            'task_completion' => [
                'total' => $total,
                'completed' => $done,
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'pending' => $tasks->where('status', 'pending')->count(),
                'rate' => $total > 0 ? round($done / $total * 100, 1) : 0,
            ],
            'idle_tasks_by_category' => $tasksByCategory($idleTasks),
            'overdue_tasks_by_category' => $tasksByCategory($overdueTasks),
            'leads_funnel' => $co(Lead::query())
                ->selectRaw('status, count(*) as count')->groupBy('status')->orderByDesc('count')->get(),
            'top_cost_categories' => EventExpense::where('event_expenses.company_id', $companyId)->whereNotIn('event_expenses.status',['rejected','reversed'])
                ->leftJoin('expense_categories', 'event_expenses.expense_category_id', '=', 'expense_categories.id')
                ->selectRaw('COALESCE(expense_categories.name, \'Other\') as name, SUM(amount) as total')
                ->groupBy('name')->orderByDesc('total')->limit(5)->get(),
            'vendor_burn_rate' => $vendorPayments,
            'clients' => [
                'total' => $clientIds->count(),
                'repeat' => $repeat,
                'churned' => $churned,
                'new_no_events' => max(0, $new),
            ],
        ]]);
    }
}
