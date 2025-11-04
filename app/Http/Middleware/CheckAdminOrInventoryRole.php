<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrInventoryRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user from request or Auth facade (try multiple guards)
        $user = $request->user() ?: auth()->user() ?: auth('api')->user();
        
        // Debug logging for demo mode
        if (config('app.debug')) {
            \Log::info('CheckAdminOrInventoryRole middleware', [
                'user' => $user ? $user->toArray() : null,
                'user_type' => $user ? $user->type : null,
                'allowed_types' => ['admin', 'inventory_staff']
            ]);
        }
        
        // TEMPORARY FIX: Allow all demo token requests
        $token = request()->bearerToken();
        if ($token && str_starts_with($token, 'demo-token-')) {
            // Demo mode - allow all operations
            return $next($request);
        }
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. No user found.',
                'debug' => config('app.debug') ? ['user_exists' => false] : null
            ], 403);
        }
        
        // Get user type - try multiple ways to access it
        $userType = $user->type ?? $user->getAttribute('type') ?? null;
        
        // Allow admin, manager, and inventory_staff
        $allowedTypes = ['admin', 'manager', 'inventory_staff'];
        
        if (!$userType || !in_array($userType, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin or Inventory staff access required.',
                'debug' => config('app.debug') ? [
                    'user_exists' => true,
                    'user_type' => $userType,
                    'user_attributes' => method_exists($user, 'getAttributes') ? $user->getAttributes() : 'N/A',
                    'allowed_types' => $allowedTypes,
                    'user_class' => get_class($user),
                    'token' => $token ? 'present' : 'missing'
                ] : null
            ], 403);
        }

        return $next($request);
    }
}
