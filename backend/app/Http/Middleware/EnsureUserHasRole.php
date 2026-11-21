<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * RBAC guard for the API. Usage: ->middleware('role:super_admin,admin,accountant')
     * Roles come from the users.role column seeded by DatabaseSeeder.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || $user->status !== 'active' || ! $user->company_id || ! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden. This endpoint requires one of the roles: '.implode(', ', $roles).'.',
            ], 403);
        }

        return $next($request);
    }
}
