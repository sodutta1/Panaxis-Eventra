<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreVendorRequest;
use App\Http\Requests\V1\UpdateVendorRequest;
use App\Http\Resources\V1\VendorResource;
use App\Models\Vendor;
use App\Models\VendorCategory;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Vendor::with('category'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('vendor_category_id', $request->category_id);
        }


        return $this->listResponse($query, $request, VendorResource::class);
    }

    public function store(StoreVendorRequest $request)
    {
        $vendor = Vendor::create(array_merge($request->validated(), [
            'company_id' => $this->companyId($request),
            'vendor_code' => 'VND-' . strtoupper(substr(md5((string) microtime(true)), 0, 6)),
            'status' => 'active',
        ]));

        return (new VendorResource($vendor))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $vendor)
    {
        return new VendorResource($this->findForCompany(Vendor::with('category'), $request, $vendor));
    }

    public function update(UpdateVendorRequest $request, int $vendor)
    {
        $model = $this->findForCompany(Vendor::query(), $request, $vendor);
        $model->update($request->validated());

        return new VendorResource($model->fresh('category'));
    }

    public function destroy(Request $request, int $vendor)
    {
        $model = $this->findForCompany(Vendor::query(), $request, $vendor);

        // Deleting a vendor cascade-deletes its work orders, which would
        // silently erase recorded vendor payments. Refuse instead, same
        // contract as client/invoice deletes.
        if ($model->assignments()->where('paid_amount', '>', 0)->exists()) {
            return response()->json([
                'message' => 'This vendor has recorded work-order payments and cannot be deleted. Delete is blocked to protect financial records.',
            ], 409);
        }

        $model->delete();

        return response()->json(['message' => 'Vendor deleted.']);
    }

    public function categories(Request $request)
    {
        $categories = $this->scopeCompany(VendorCategory::query(), $request)->orderBy('name')->get();

        return response()->json(['data' => $categories]);
    }

}
