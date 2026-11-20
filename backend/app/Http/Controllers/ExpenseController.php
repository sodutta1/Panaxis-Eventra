<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventExpense;
use App\Models\ExpenseCategory;
use App\Models\Event;
use App\Models\Vendor;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = EventExpense::with(['event', 'category', 'vendor', 'approver']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('incurred_date', 'desc')->paginate(15);
        $events = Event::orderBy('title')->get();
        $categories = ExpenseCategory::all();
        $vendors = Vendor::orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'events', 'categories', 'vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'incurred_date' => 'required|date',
            'payment_method' => 'required|string',
            'receipt_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $expNumber = 'EXP-' . date('Y') . '-' . rand(1000, 9999);

        EventExpense::create(array_merge($validated, [
            'company_id' => $companyId,
            'expense_number' => $expNumber,
            'approved_by' => auth()->id(),
            'status' => 'paid',
        ]));

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function approve(EventExpense $expense)
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Expense approved.');
    }
}

