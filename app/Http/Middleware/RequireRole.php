<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireRole
{
    /**
     * Usage: middleware('role:owner,admin')
     * Passes if the authenticated user has ANY of the listed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $userRoles = $user->roles()->pluck('name')->toArray();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
