<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Models\Event;
use App\Models\EventBudget;
use App\Models\EventBudgetItem;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use ScopesToCompany;

    private function budgetFor(Request $request, int $eventId): EventBudget
    {
        $event = $this->findForCompany(Event::query(), $request, $eventId);

        return EventBudget::firstOrCreate(
            ['event_id' => $event->id, 'company_id' => $event->company_id],
            ['status' => 'draft', 'contingency_percent' => 0, 'contingency_amount' => 0, 'total_estimated_income' => 0, 'total_estimated_cost' => 0, 'total_actual_cost' => 0]
        );
    }

    public function show(Request $request, int $event)
    {
        $budget = $this->budgetFor($request, $event)->load('items');

        return response()->json(['data' => $budget]);
    }

    public function addItem(Request $request, int $event)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'estimated_cost' => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $budget = $this->budgetFor($request, $event);
        // actual_cost is NOT NULL in the schema - omit it when not provided
        $item = $budget->items()->create(array_filter($data, fn ($v) => $v !== null));
        $this->recalc($budget);

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, int $item)
    {
        $data = $request->validate([
            'category' => 'sometimes|string|max:255',
            'item_name' => 'sometimes|string|max:255',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $model = EventBudgetItem::whereHas('budget', fn ($q) => $q->where('company_id', $this->companyId($request)))->findOrFail($item);
        $model->update(array_filter($data, fn ($v) => $v !== null));
        $this->recalc($model->budget);

        return response()->json(['data' => $model->fresh()]);
    }

    private function recalc(EventBudget $budget): void
    {
        $budget->update([
            'total_estimated_cost' => $budget->items()->sum('estimated_cost'),
            'total_actual_cost' => $budget->items()->sum('actual_cost'),
        ]);
    }
}
