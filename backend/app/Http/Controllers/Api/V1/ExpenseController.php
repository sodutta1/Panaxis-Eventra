<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreExpenseRequest;
use App\Http\Requests\V1\UpdateExpenseRequest;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\EventExpense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(EventExpense::query(), $request);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->listResponse($query, $request, ExpenseResource::class);
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = EventExpense::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'expense_number' => 'EXP-' . date('Y') . '-' . rand(1000, 9999),
            'approved_by' => $request->user()->id,
            'status' => 'paid',
        ]));

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function approve(Request $request, int $expense)
    {
        $model = $this->findForCompany(EventExpense::query(), $request, $expense);
        $model->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return new ExpenseResource($model->fresh());
    }

    public function show(Request $request, int $expense)
    {
        return new ExpenseResource($this->findForCompany(EventExpense::query(), $request, $expense));
    }

    public function update(UpdateExpenseRequest $request, int $expense)
    {
        $model = $this->findForCompany(EventExpense::query(), $request, $expense);
        $model->update($request->validated());

        return new ExpenseResource($model->fresh());
    }

    public function destroy(Request $request, int $expense)
    {
        $this->findForCompany(EventExpense::query(), $request, $expense)->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

}
