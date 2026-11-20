<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorCategory;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with(['category'])->withCount('assignments', 'expenses');

        if ($request->filled('category_id')) {
            $query->where('vendor_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->orderBy('name')->paginate(12);
        $categories = VendorCategory::all();

        return view('vendors.index', compact('vendors', 'categories'));
    }

    public function create()
    {
        $categories = VendorCategory::all();
        return view('vendors.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'vendor_category_id' => 'required|exists:vendor_categories,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'services_offered' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'bank_details' => 'nullable|string',
        ]);

        $companyId = auth()->user()->company_id ?? 1;
        $vendorCode = 'VND-' . rand(100, 999);

        Vendor::create(array_merge($validated, [
            'company_id' => $companyId,
            'vendor_code' => $vendorCode,
            'rating' => 5.0,
            'status' => 'active',
        ]));

        return redirect()->route('vendors.index')->with('success', 'Vendor registered successfully.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['category', 'assignments.event', 'expenses.event']);
        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $categories = VendorCategory::all();
        return view('vendors.edit', compact('vendor', 'categories'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'vendor_category_id' => 'required|exists:vendor_categories,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'services_offered' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor updated.');
    }
}

