<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Requests\V1\StoreVendorRequest;
use App\Http\Requests\V1\UpdateVendorRequest;
use App\Http\Resources\V1\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ScopesToCompany;

    public function index(Request $request)
    {
        $query = $this->scopeCompany(Vendor::with('category'), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('vendor_category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%");
            });
        }

        return VendorResource::collection($query->orderBy('name')->paginate(15));
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
        $this->findForCompany(Vendor::query(), $request, $vendor)->delete();

        return response()->json(['message' => 'Vendor deleted.']);
    }
}
