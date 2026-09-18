<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;

class EnsureTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Tenant not identified.'], 403);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found.'], 403);
        }

        // Store tenant on the request for controllers to access
        $request->merge(['_tenant_id' => $tenant->id]);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
