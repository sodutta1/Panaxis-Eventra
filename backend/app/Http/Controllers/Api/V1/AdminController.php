<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ScopesToCompany;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use ScopesToCompany;

    private const ROLES = ['super_admin', 'company_admin', 'admin', 'event_manager', 'sales_executive', 'accountant', 'staff', 'client', 'vendor'];

    public function users(Request $request)
    {
        $users = $this->scopeCompany(User::query(), $request)->orderBy('name')->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'status' => $u->status,
            ]);

        return response()->json(['data' => $users, 'roles' => self::ROLES]);
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
