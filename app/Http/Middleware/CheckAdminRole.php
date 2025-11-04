<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TEMPORARY FIX: Allow all demo token requests
        $token = $request->bearerToken();
        if ($token && str_starts_with($token, 'demo-token-')) {
            // Demo mode - allow all operations
            return $next($request);
        }
        
        // Get user from request or Auth facade (try multiple guards)
        $user = $request->user() ?: auth()->user() ?: auth('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. No user found.'
            ], 403);
        }
        
        // Get user type - try multiple ways to access it
        $userType = $user->type ?? $user->getAttribute('type') ?? null;
        
        // Allow admin, manager, and inventory_staff
        $allowedTypes = ['admin', 'manager', 'inventory_staff'];
        
        if (!$userType || !in_array($userType, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin, Manager, or Inventory Clerk role required.',
                'debug' => config('app.debug') ? [
                    'user_exists' => true,
                    'user_type' => $userType,
                    'allowed_types' => $allowedTypes
                ] : null
            ], 403);
        }

        return $next($request);
    }
}