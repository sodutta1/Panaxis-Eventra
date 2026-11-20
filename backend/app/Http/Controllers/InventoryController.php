<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('category')->withCount('allocations');

        if ($request->filled('category_id')) {
            $query->where('inventory_category_id', $request->category_id);
        }

        $items = $query->orderBy('name')->paginate(15);
        $categories = InventoryCategory::all();

        return view('inventory.index', compact('items', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'total_quantity' => 'required|integer|min:1',
            'unit' => 'required|string',
            'rental_rate' => 'required|numeric|min:0',
            'replacement_cost' => 'nullable|numeric|min:0',
            'condition' => 'required|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $itemCode = 'INV-' . rand(100, 999);

        InventoryItem::create(array_merge($validated, [
            'company_id' => $companyId,
            'item_code' => $itemCode,
            'available_quantity' => $validated['total_quantity'],
        ]));

        return back()->with('success', 'Equipment / inventory item added to catalog.');
    }
}

