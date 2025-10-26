<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SystemStatusController extends Controller
{
    /**
     * Get system health status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck(Request $request)
    {
        $checks = [];
        
        // Database check
        $checks['database'] = $this->checkDatabase();
        
        // Cache check
        $checks['cache'] = $this->checkCache();
        
        // Application check
        $checks['application'] = $this->checkApplication();
        
        // Overall status
        $overallStatus = $this->calculateOverallStatus($checks);
        
        return response()->json([
            'success' => true,
            'status' => $overallStatus,
            'timestamp' => Carbon::now()->toIso8601String(),
            'checks' => $checks
        ]);
    }
    
    /**
     * Get detailed system metrics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics(Request $request)
    {
        $metrics = [];
        
        // Database metrics
        $metrics['database'] = $this->getDatabaseMetrics();
        
        // System metrics
        $metrics['system'] = $this->getSystemMetrics();
        
        // Application metrics
        $metrics['application'] = $this->getApplicationMetrics();
        
        return response()->json([
            'success' => true,
            'timestamp' => Carbon::now()->toIso8601String(),
            'metrics' => $metrics
        ]);
    }
    
    /**
     * Check database connectivity and performance
     *
     * @return array
     */
    private function checkDatabase()
    {
        $startTime = microtime(true);
        
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'message' => 'Database connection is healthy'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ];
        }
    }
    
    /**
     * Check cache functionality
     *
     * @return array
     */
    private function checkCache()
    {
        $startTime = microtime(true);
        
        try {
            $testKey = 'health_check_test_' . uniqid();
            $testValue = 'test_value_' . time();
            
            // Test cache write
            Cache::put($testKey, $testValue, 60);
            
            // Test cache read
            $retrievedValue = Cache::get($testKey);
            
            // Clean up
            Cache::forget($testKey);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            if ($retrievedValue === $testValue) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => round($responseTime, 2),
                    'message' => 'Cache is functioning properly'
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write mismatch'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Cache functionality failed'
            ];
        }
    }
    
    /**
     * Check application status
     *
     * @return array
     */
    private function checkApplication()
    {
        try {
            // Check if we can access a simple model
            $userCount = DB::table('users')->count();
            
            return [
                'status' => 'healthy',
                'user_count' => $userCount,
                'message' => 'Application is running normally'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Application check failed'
            ];
        }
    }
    
    /**
     * Calculate overall system status
     *
     * @param array $checks
     * @return string
     */
    private function calculateOverallStatus($checks)
    {
        foreach ($checks as $check) {
            if (isset($check['status']) && $check['status'] !== 'healthy') {
                return 'degraded';
            }
        }
        
        return 'healthy';
    }
    
    /**
     * Get database metrics
     *
     * @return array
     */
    private function getDatabaseMetrics()
    {
        try {
            // Get table information
            $tables = DB::select("SHOW TABLE STATUS");
            
            $totalRows = 0;
            $totalSize = 0;
            $tableCount = count($tables);
            
            foreach ($tables as $table) {
                $totalRows += $table->Rows ?? 0;
                $totalSize += ($table->Data_length + $table->Index_length) ?? 0;
            }
            
            // Get connection count
            $connectionCount = 0;
            $processList = DB::select("SHOW PROCESSLIST");
            $connectionCount = count($processList);
            
            return [
                'table_count' => $tableCount,
                'total_rows' => $totalRows,
                'total_size_bytes' => $totalSize,
                'connection_count' => $connectionCount,
                'formatted_size' => $this->formatBytes($totalSize)
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get system metrics
     *
     * @return array
     */
    private function getSystemMetrics()
    {
        return [
            'memory_usage_bytes' => memory_get_usage(true),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'formatted_memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'formatted_memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : null
        ];
    }
    
    /**
     * Get application metrics
     *
     * @return array
     */
    private function getApplicationMetrics()
    {
        try {
            // Get counts for various entities
            $userCount = DB::table('users')->count();
            $productCount = DB::table('products')->count();
            $orderCount = DB::table('orders')->count();
            
            // Get recent activity
            $recentUsers = DB::table('users')
                ->where('created_at', '>', Carbon::now()->subDay())
                ->count();
                
            $recentOrders = DB::table('orders')
                ->where('ord_date', '>', Carbon::now()->subDay())
                ->count();
            
            return [
                'counts' => [
                    'users' => $userCount,
                    'products' => $productCount,
                    'orders' => $orderCount
                ],
                'recent_activity' => [
                    'new_users_today' => $recentUsers,
                    'new_orders_today' => $recentOrders
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}