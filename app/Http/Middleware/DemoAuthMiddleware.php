<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DemoAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        // Check if it's a demo token
        if ($token && str_starts_with($token, 'demo-token-')) {
            // Create a demo user for the request
            $demoUser = new User();
            $demoUser->id = 1;
            $demoUser->name = 'Demo Admin';
            $demoUser->email = 'admin@example.com';
            $demoUser->user_type = 'admin'; // Use user_type instead of type
            $demoUser->status = 'active';
            $demoUser->exists = true; // Mark as existing to avoid save attempts
            
            // Ensure all attributes are properly set
            $demoUser->setAttribute('id', 1);
            $demoUser->setAttribute('name', 'Demo Admin');
            $demoUser->setAttribute('email', 'admin@example.com');
            $demoUser->setAttribute('user_type', 'admin'); // Use user_type instead of type
            $demoUser->setAttribute('status', 'active');
            
            // Set the demo user as authenticated
            Auth::setUser($demoUser);
            
            // Also set the user on the request for other middleware
            $request->setUserResolver(function () use ($demoUser) {
                return $demoUser;
            });
            
            // Debug logging
            if (config('app.debug')) {
                \Log::info('DemoAuthMiddleware: Demo user set', [
                    'user_id' => $demoUser->id,
                    'user_type' => $demoUser->user_type,
                    'type_accessor' => $demoUser->type, // This should now work
                    'user_name' => $demoUser->name,
                    'user_attributes' => $demoUser->getAttributes(),
                    'user_class' => get_class($demoUser),
                    'token' => $token
                ]);
            }
            
            return $next($request);
        }
        
        // If not a demo token, try JWT authentication
        try {
            // Check if user is authenticated via JWT (api guard)
            if (Auth::guard('api')->check()) {
                return $next($request);
            }
            
            // Try to authenticate with JWT manually
            if ($token) {
                try {
                    $user = Auth::guard('api')->user();
                    if ($user) {
                        return $next($request);
                    }
                } catch (\Exception $jwtError) {
                    \Log::warning('JWT authentication failed', ['error' => $jwtError->getMessage()]);
                }
            }
            
            // If no authentication, return unauthorized
            return response()->json(['message' => 'Unauthorized'], 401);
        } catch (\Exception $e) {
            // If JWT fails and no demo token, return unauthorized
            \Log::error('Authentication middleware error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }
}