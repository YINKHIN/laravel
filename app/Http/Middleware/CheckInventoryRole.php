<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInventoryRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->type !== 'inventory_staff') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Inventory staff access required.'
            ], 403);
        }

        return $next($request);
    }
}
