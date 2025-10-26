<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Import;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThirdPartyIntegrationController extends Controller
{
    /**
     * Get real-time inventory levels for all products
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventoryLevels(Request $request)
    {
        try {
            $query = Product::select(
                'id',
                'pro_name as product_name',
                'qty as current_stock',
                'reorder_point',
                'batch_number',
                'expiration_date'
            );
            
            // Apply filters
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('low_stock')) {
                $query->whereColumn('qty', '<=', 'reorder_point');
            }
            
            if ($request->has('expired')) {
                $query->where('expiration_date', '<', now());
            }
            
            if ($request->has('near_expiration')) {
                $query->where('expiration_date', '>=', now())
                      ->where('expiration_date', '<=', now()->addDays(30));
            }
            
            $products = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory levels: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get product movement history (imports and sales)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductMovementHistory(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product ID is required'
                ], 400);
            }
            
            // Get import history
            $importHistory = DB::table('import_details')
                ->join('imports', 'import_details.imp_code', '=', 'imports.id')
                ->join('suppliers', 'imports.sup_id', '=', 'suppliers.id')
                ->select(
                    'imports.imp_date as date',
                    'import_details.qty as quantity',
                    'import_details.price as unit_price',
                    'suppliers.supplier as supplier_name',
                    DB::raw('"import" as transaction_type')
                )
                ->where('import_details.pro_code', $productId)
                ->orderBy('imports.imp_date', 'desc');
            
            // Get sales history
            $salesHistory = DB::table('order_details')
                ->join('orders', 'order_details.ord_code', '=', 'orders.id')
                ->join('customers', 'orders.cus_id', '=', 'customers.id')
                ->select(
                    'orders.ord_date as date',
                    DB::raw('-order_details.qty as quantity'), // Negative for sales
                    'order_details.price as unit_price',
                    'customers.cus_name as customer_name',
                    DB::raw('"sale" as transaction_type')
                )
                ->where('order_details.pro_code', $productId)
                ->orderBy('orders.ord_date', 'desc');
            
            // Combine and paginate results
            $history = $importHistory->union($salesHistory)
                ->orderBy('date', 'desc')
                ->paginate(50);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product movement history: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get supplier performance metrics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupplierPerformance(Request $request)
    {
        try {
            $query = DB::table('imports')
                ->join('suppliers', 'imports.sup_id', '=', 'suppliers.id')
                ->join('import_details', 'imports.id', '=', 'import_details.imp_code')
                ->select(
                    'suppliers.id as supplier_id',
                    'suppliers.supplier as supplier_name',
                    DB::raw('COUNT(DISTINCT imports.id) as total_imports'),
                    DB::raw('SUM(import_details.qty) as total_quantity'),
                    DB::raw('SUM(import_details.amount) as total_value'),
                    DB::raw('AVG(import_details.price) as avg_unit_price')
                )
                ->groupBy('suppliers.id', 'suppliers.supplier');
            
            // Apply date filters
            if ($request->has('date_from')) {
                $query->where('imports.imp_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->where('imports.imp_date', '<=', $request->date_to);
            }
            
            $performance = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier performance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer purchase history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerPurchaseHistory(Request $request)
    {
        try {
            $customerId = $request->get('customer_id');
            
            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID is required'
                ], 400);
            }
            
            $history = DB::table('orders')
                ->join('order_details', 'orders.id', '=', 'order_details.ord_code')
                ->join('products', 'order_details.pro_code', '=', 'products.id')
                ->select(
                    'orders.id as order_id',
                    'orders.ord_date as order_date',
                    'products.pro_name as product_name',
                    'order_details.qty as quantity',
                    'order_details.price as unit_price',
                    'order_details.amount as total_amount'
                )
                ->where('orders.cus_id', $customerId)
                ->orderBy('orders.ord_date', 'desc')
                ->paginate(50);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer purchase history: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get real-time sales data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealTimeSalesData(Request $request)
    {
        try {
            $period = $request->get('period', 'daily');
            
            // Calculate date range based on period
            $startDate = $this->getStartDate($period);
            $endDate = now();
            
            $salesData = DB::table('orders')
                ->join('order_details', 'orders.id', '=', 'order_details.ord_code')
                ->select(
                    DB::raw('DATE(orders.ord_date) as date'),
                    DB::raw('SUM(order_details.qty) as total_quantity'),
                    DB::raw('SUM(order_details.amount) as total_revenue'),
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders')
                )
                ->whereBetween('orders.ord_date', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(orders.ord_date)'))
                ->orderBy('date', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $salesData,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time sales data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get product recommendations based on sales history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductRecommendations(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $period = $request->get('period', 'monthly');
            
            // Calculate date range based on period
            $startDate = $this->getStartDate($period);
            $endDate = now();
            
            $recommendations = DB::table('order_details')
                ->join('orders', 'order_details.ord_code', '=', 'orders.id')
                ->join('products', 'order_details.pro_code', '=', 'products.id')
                ->select(
                    'products.id as product_id',
                    'products.pro_name as product_name',
                    DB::raw('SUM(order_details.qty) as total_sold'),
                    DB::raw('SUM(order_details.amount) as total_revenue')
                )
                ->whereBetween('orders.ord_date', [$startDate, $endDate])
                ->groupBy('products.id', 'products.pro_name')
                ->orderBy('total_sold', 'desc')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product recommendations: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate start date based on period
     * 
     * @param string $period
     * @return \Carbon\Carbon
     */
    private function getStartDate($period)
    {
        $now = now();
        
        switch ($period) {
            case 'weekly':
                return $now->copy()->subWeek();
            case 'monthly':
                return $now->copy()->subMonth();
            case 'yearly':
                return $now->copy()->subYear();
            case 'daily':
            default:
                return $now->copy()->startOfDay();
        }
    }
    
    /**
     * Webhook endpoint for external systems to push data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookReceiver(Request $request)
    {
        try {
            $eventType = $request->get('event_type');
            $payload = $request->get('payload');
            
            if (!$eventType || !$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event type and payload are required'
                ], 400);
            }
            
            // Process different event types
            switch ($eventType) {
                case 'product_created':
                    // Handle product creation event
                    $this->handleProductCreated($payload);
                    break;
                case 'order_created':
                    // Handle order creation event
                    $this->handleOrderCreated($payload);
                    break;
                case 'stock_updated':
                    // Handle stock update event
                    $this->handleStockUpdated($payload);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported event type: ' . $eventType
                    ], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process webhook: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Handle product created webhook
     * 
     * @param array $payload
     * @return void
     */
    private function handleProductCreated($payload)
    {
        // Implementation for handling product creation
        // This would typically create a new product in the database
    }
    
    /**
     * Handle order created webhook
     * 
     * @param array $payload
     * @return void
     */
    private function handleOrderCreated($payload)
    {
        // Implementation for handling order creation
        // This would typically create a new order in the database
    }
    
    /**
     * Handle stock updated webhook
     * 
     * @param array $payload
     * @return void
     */
    private function handleStockUpdated($payload)
    {
        // Implementation for handling stock updates
        // This would typically update product quantities in the database
    }
}