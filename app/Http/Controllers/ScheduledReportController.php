<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduledReportController extends Controller
{
    /**
     * Get best selling products report
     */
    public function bestSellingProducts(Request $request)
    {
        try {
            $period = $request->get('period', 'daily'); // daily, weekly, monthly, yearly
            $limit = $request->get('limit', 10);

            // Calculate date range based on period
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();

            // Get best selling products
            $bestSellingProducts = OrderDetail::join('orders', 'order_details.ord_code', '=', 'orders.id')
                ->join('products', 'order_details.pro_code', '=', 'products.id')
                ->select(
                    'products.id as product_id',
                    'products.pro_name as product_name',
                    DB::raw('SUM(order_details.qty) as total_quantity'),
                    DB::raw('SUM(order_details.amount) as total_revenue')
                )
                // More flexible date filtering
                ->where('orders.ord_date', '>=', $startDate)
                ->where('orders.ord_date', '<=', $endDate)
                ->groupBy('products.id', 'products.pro_name')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $bestSellingProducts,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve best selling products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock products report
     */
    public function lowStockProducts(Request $request)
    {
        try {
            $threshold = $request->get('threshold', 10); // Default threshold of 10 units

            // Note: The products table doesn't have a 'status' column
            // So we're only filtering by quantity threshold
            $lowStockProducts = Product::select(
                    'id',
                    'pro_name',
                    'qty as current_stock'
                )
                ->where('qty', '<=', $threshold)
                ->orderBy('qty', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lowStockProducts,
                'threshold' => $threshold
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve low stock products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory summary report
     */
    public function inventorySummary(Request $request)
    {
        try {
            $period = $request->get('period', 'daily');
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();

            // Get total products
            $totalProducts = Product::count();

            // Note: The products table doesn't have a 'status' column
            // So we can't filter by active products
            $activeProducts = $totalProducts; // For now, assuming all products are active

            // Get low stock products
            $lowStockProducts = Product::where('qty', '<=', 10)->count();

            // Get out of stock products
            $outOfStockProducts = Product::where('qty', 0)->count();

            // Get recent sales data
            $recentSales = OrderDetail::join('orders', 'order_details.ord_code', '=', 'orders.id')
                ->select(
                    DB::raw('SUM(order_details.qty) as total_sold'),
                    DB::raw('SUM(order_details.amount) as total_revenue')
                )
                ->whereBetween('orders.ord_date', [$startDate, $endDate])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeProducts,
                    'low_stock_products' => $lowStockProducts,
                    'out_of_stock_products' => $outOfStockProducts,
                    'recent_sales' => [
                        'quantity_sold' => $recentSales->total_sold ?? 0,
                        'revenue' => $recentSales->total_revenue ?? 0
                    ]
                ],
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate start date based on period
     */
    private function getStartDate($period)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'weekly':
                return $now->copy()->subWeek();
            case 'monthly':
                return $now->copy()->subMonth();
            case 'yearly':
                return $now->copy()->subYear();
            case 'daily':
            default:
                // For daily period, include today by starting from the beginning of today
                return $now->copy()->startOfDay();
        }
    }
}