<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use ScopesToCompany;
    use \App\Http\Controllers\Api\V1\Concerns\QueriesLists;

    private const ROLES = ['super_admin', 'company_admin', 'admin', 'event_manager', 'sales_executive', 'accountant', 'staff', 'client', 'vendor'];

    /**
     * POST /api/v1/admin/branding/logo
     * Upload the company logo used on quotations, invoices, e-tickets,
     * work orders and the public booking page.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $company = Company::findOrFail($this->companyId($request));
        $path = $request->file('logo')->store('branding', 'public');
        $company->forceFill(['logo_path' => $path])->save();

        return response()->json(['data' => ['logo_path' => $path, 'logo_url' => $company->fresh()->logo_url]]);
    }

    public function users(Request $request)
    {
        $response = $this->listResponse($this->scopeCompany(User::query(), $request), $request);
        if ($request->filled('format')) return $response;
        $data = $response->getData(true);
        $data['data'] = array_map(fn($u) => array_intersect_key($u,array_flip(['id','name','email','role','status'])), $data['data']);
        return response()->json($data + ['roles'=>self::ROLES]);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'min:8', 'max:4096', function ($attribute, $value, $fail) {
                if (is_string($value) && config('hashing.driver') === 'bcrypt' && strlen($value) > 72) {
                    $fail('The password must not exceed 72 bytes when bcrypt is used.');
                }
            }],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::create([
            'company_id' => $this->companyId($request),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'active',
        ]);

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]], 201);
    }

    public function updateUser(Request $request, int $user)
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(self::ROLES)],
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $model = $this->findForCompany(User::query(), $request, $user);
        $model->update($data);

        // Deactivating an account must end its existing API sessions too,
        // not only block new logins.
        if (($data['status'] ?? null) === 'inactive') {
            $model->tokens()->delete();
        }

        return response()->json(['data' => ['id' => $model->id, 'name' => $model->name, 'email' => $model->email, 'role' => $model->role, 'status' => $model->status]]);
    }
}
