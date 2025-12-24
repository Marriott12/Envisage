<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request - Check if user has required permission(s)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$permissions
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
                'error' => 'unauthenticated',
            ], 401);
        }

        $user = Auth::user();

        // Check if user has all required permissions
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have the required permission to access this resource.',
                    'error' => 'forbidden',
                    'data' => [
                        'required_permission' => $permission,
                        'your_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
