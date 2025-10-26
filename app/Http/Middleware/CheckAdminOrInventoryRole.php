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
        if (!$request->user() || !in_array($request->user()->type, ['admin', 'inventory_staff'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin or Inventory staff access required.'
            ], 403);
        }

        return $next($request);
    }
}
