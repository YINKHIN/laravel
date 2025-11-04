<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManagerRole
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
        
        // Get user from request or Auth facade (for demo mode)
        $user = $request->user() ?: auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. No user found.'
            ], 403);
        }
        
        // Get user type - try multiple ways to access it
        $userType = $user->type ?? $user->getAttribute('type') ?? null;
        
        // Allow admin and manager roles
        $allowedRoles = ['admin', 'manager'];
        
        if (!$userType || !in_array($userType, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin or Manager role required.',
                'debug' => config('app.debug') ? [
                    'user_type' => $userType,
                    'allowed_types' => $allowedRoles,
                    'user_class' => get_class($user)
                ] : null
            ], 403);
        }

        return $next($request);
    }
}