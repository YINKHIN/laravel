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
        // Allow admin, manager, and inventory clerk roles to have admin privileges
        $allowedRoles = ['admin', 'manager', 'inventory'];
        
        // Use the getTypeAttribute accessor method to get the correct user type
        if (!$request->user() || !in_array($request->user()->type, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin, Manager, or Inventory Clerk role required.'
            ], 403);
        }

        return $next($request);
    }
}