<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBudget;
use App\Models\EventBudgetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    use ScopesToCompany;

    private function budgetFor(Request $request, int $eventId): EventBudget
    {
        $event = $this->findForCompany(Event::query()->lockForUpdate(), $request, $eventId);

        return EventBudget::query()->lockForUpdate()->firstOrCreate(
            ['event_id' => $event->id, 'company_id' => $event->company_id],
            ['status' => 'draft', 'contingency_percent' => 0, 'contingency_amount' => 0, 'total_estimated_income' => 0, 'total_estimated_cost' => 0, 'total_actual_cost' => 0]
        );
    }

    public function show(Request $request, int $event)
    {
        return DB::transaction(fn () => response()->json(['data' => $this->budgetFor($request, $event)->load('items')]), 3);
    }

    public function addItem(Request $request, int $event)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255', 'item_name' => 'required|string|max:255',
            'estimated_cost' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'actual_cost' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'notes' => 'nullable|string|max:16000',
        ]);

        return DB::transaction(function () use ($request, $event, $data) {
            $budget = $this->budgetFor($request, $event);
            $item = $budget->items()->create(array_filter($data, fn ($v) => $v !== null));
            $this->recalc($budget);

            return response()->json(['data' => $item], 201);
        }, 3);
    }

    public function updateItem(Request $request, int $item)
    {
        $data = $request->validate([
            'category' => 'sometimes|required|string|max:255', 'item_name' => 'sometimes|required|string|max:255',
            'estimated_cost' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'actual_cost' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'notes' => 'nullable|string|max:16000',
        ]);
        $candidate = EventBudgetItem::whereHas('budget', fn ($q) => $q->where('company_id', $this->companyId($request)))->findOrFail($item);

        return DB::transaction(function () use ($request, $candidate, $data) {
            $budget = $this->findForCompany(EventBudget::query()->lockForUpdate(), $request, $candidate->event_budget_id);
            $model = $budget->items()->lockForUpdate()->findOrFail($candidate->id);
            $model->update(array_filter($data, fn ($v) => $v !== null));
            $this->recalc($budget);

            return response()->json(['data' => $model->fresh()]);
        }, 3);
    }

    private function recalc(EventBudget $budget): void
    {
        $estimated = $budget->items()->sum('estimated_cost');
        $actual = $budget->items()->sum('actual_cost');
        if ((float) $estimated > 9999999999.99 || (float) $actual > 9999999999.99) {
            throw ValidationException::withMessages(['estimated_cost' => 'Budget total exceeds the supported amount.']);
        }
        $budget->update(['total_estimated_cost' => $estimated, 'total_actual_cost' => $actual]);
    }
}
