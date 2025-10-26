<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SystemMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:monitor 
                            {--log : Log results to file}
                            {--alert : Send alert if issues detected}
                            {--threshold=50 : Performance threshold percentage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system performance and health';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting system monitoring...');
        
        $results = [];
        
        // Check database connection
        $results['database'] = $this->checkDatabase();
        
        // Check system resources
        $results['resources'] = $this->checkSystemResources();
        
        // Check application performance
        $results['performance'] = $this->checkApplicationPerformance();
        
        // Check scheduled tasks
        $results['scheduled_tasks'] = $this->checkScheduledTasks();
        
        // Display results
        $this->displayResults($results);
        
        // Log results if requested
        if ($this->option('log')) {
            $this->logResults($results);
        }
        
        // Send alert if issues detected
        if ($this->option('alert')) {
            $this->checkForAlerts($results);
        }
        
        return 0;
    }
    
    /**
     * Check database health
     *
     * @return array
     */
    private function checkDatabase()
    {
        $startTime = microtime(true);
        
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Check table sizes
            $tables = DB::select("SHOW TABLE STATUS");
            $totalSize = 0;
            $largestTable = '';
            $largestSize = 0;
            
            foreach ($tables as $table) {
                $size = $table->Data_length + $table->Index_length;
                $totalSize += $size;
                
                if ($size > $largestSize) {
                    $largestSize = $size;
                    $largestTable = $table->Name;
                }
            }
            
            // Check for slow queries
            $slowQueries = DB::select("SHOW VARIABLES LIKE 'slow_query_log'");
            $slowQueryLog = isset($slowQueries[0]) ? $slowQueries[0]->Value : 'Unknown';
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 2),
                'total_size' => $this->formatBytes($totalSize),
                'largest_table' => $largestTable,
                'largest_table_size' => $this->formatBytes($largestSize),
                'slow_query_log' => $slowQueryLog
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check system resources
     *
     * @return array
     */
    private function checkSystemResources()
    {
        try {
            // Get memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            
            // Get server load (if available)
            $load = [];
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
            }
            
            return [
                'status' => 'info',
                'memory_usage' => $this->formatBytes($memoryUsage),
                'memory_peak' => $this->formatBytes($memoryPeak),
                'cpu_load' => $load
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check application performance
     *
     * @return array
     */
    private function checkApplicationPerformance()
    {
        try {
            // Check cache performance
            $cacheStartTime = microtime(true);
            cache(['monitor_test' => 'test_value'], 1);
            cache()->get('monitor_test');
            $cacheEndTime = microtime(true);
            $cacheTime = ($cacheEndTime - $cacheStartTime) * 1000;
            
            // Check session performance
            $sessionStartTime = microtime(true);
            session(['monitor_test' => 'test_value']);
            session()->get('monitor_test');
            $sessionEndTime = microtime(true);
            $sessionTime = ($sessionEndTime - $sessionStartTime) * 1000;
            
            return [
                'status' => 'info',
                'cache_response_time' => round($cacheTime, 2),
                'session_response_time' => round($sessionTime, 2)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check scheduled tasks
     *
     * @return array
     */
    private function checkScheduledTasks()
    {
        try {
            // Check if scheduled reports command exists
            $commandExists = class_exists(\App\Console\Commands\GenerateScheduledReports::class);
            
            // Check last run time (if logs exist)
            $lastRun = 'Unknown';
            $logFile = storage_path('logs/laravel.log');
            
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                if (preg_match('/Generate scheduled inventory reports/', $logContent, $matches)) {
                    $lastRun = 'Recent';
                }
            }
            
            return [
                'status' => $commandExists ? 'healthy' : 'warning',
                'command_exists' => $commandExists,
                'last_run' => $lastRun
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Display monitoring results
     *
     * @param array $results
     * @return void
     */
    private function displayResults($results)
    {
        $this->info("\n=== System Monitoring Results ===\n");
        
        // Database section
        $this->info("Database Health:");
        if ($results['database']['status'] === 'healthy') {
            $this->line("  Status: ✅ Healthy");
            $this->line("  Response Time: {$results['database']['response_time']} ms");
            $this->line("  Total Size: {$results['database']['total_size']}");
            $this->line("  Largest Table: {$results['database']['largest_table']} ({$results['database']['largest_table_size']})");
        } else {
            $this->line("  Status: ❌ Error");
            $this->line("  Message: {$results['database']['message']}");
        }
        
        // Resources section
        $this->info("\nSystem Resources:");
        if ($results['resources']['status'] === 'info') {
            $this->line("  Memory Usage: {$results['resources']['memory_usage']}");
            $this->line("  Peak Memory: {$results['resources']['memory_peak']}");
            if (!empty($results['resources']['cpu_load'])) {
                $this->line("  CPU Load: " . implode(', ', array_slice($results['resources']['cpu_load'], 0, 3)));
            }
        } else {
            $this->line("  Status: ❌ Error");
            $this->line("  Message: {$results['resources']['message']}");
        }
        
        // Performance section
        $this->info("\nApplication Performance:");
        if ($results['performance']['status'] === 'info') {
            $this->line("  Cache Response Time: {$results['performance']['cache_response_time']} ms");
            $this->line("  Session Response Time: {$results['performance']['session_response_time']} ms");
        } else {
            $this->line("  Status: ❌ Error");
            $this->line("  Message: {$results['performance']['message']}");
        }
        
        // Scheduled Tasks section
        $this->info("\nScheduled Tasks:");
        if ($results['scheduled_tasks']['status'] === 'healthy') {
            $this->line("  Status: ✅ Healthy");
            $this->line("  Command Exists: Yes");
            $this->line("  Last Run: {$results['scheduled_tasks']['last_run']}");
        } else {
            $this->line("  Status: ⚠️ Warning");
            $this->line("  Command Exists: " . ($results['scheduled_tasks']['command_exists'] ? 'Yes' : 'No'));
            $this->line("  Last Run: {$results['scheduled_tasks']['last_run']}");
        }
    }
    
    /**
     * Log results to file
     *
     * @param array $results
     * @return void
     */
    private function logResults($results)
    {
        Log::info('System Monitor Results', $results);
        $this->info("\nResults logged to laravel.log");
    }
    
    /**
     * Check for alerts
     *
     * @param array $results
     * @return void
     */
    private function checkForAlerts($results)
    {
        $threshold = $this->option('threshold');
        $alerts = [];
        
        // Check database response time
        if (isset($results['database']['response_time']) && 
            $results['database']['response_time'] > $threshold) {
            $alerts[] = "Database response time is high: {$results['database']['response_time']} ms";
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->error("ALERT: {$alert}");
            }
        } else {
            $this->info("\nNo alerts detected");
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