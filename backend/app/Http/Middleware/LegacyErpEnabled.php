<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LegacyErpEnabled
{
    public function handle(Request $request, Closure $next)
    {
        // The old Blade controllers do not enforce the API authorization contract.
        // Keep them inaccessible unless a local developer deliberately opts in.
        abort_unless(app()->environment('local') && config('app.legacy_erp'), 404);

        return $next($request);
    }
}
