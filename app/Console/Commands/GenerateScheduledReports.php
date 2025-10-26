<?php

namespace App\Console\Commands;

use App\Mail\ScheduledReportMail;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ScheduledReport;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GenerateScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-scheduled 
                            {--period=daily : Report period (daily, weekly, monthly, yearly)} 
                            {--email= : Email to send report to}
                            {--admin-only : Send only to admin users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scheduled inventory reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $period = $this->option('period');
        $email = $this->option('email');
        $adminOnly = $this->option('admin-only');

        $this->info("Generating {$period} scheduled reports...");

        // Get best selling products
        $bestSellingProducts = $this->getBestSellingProducts($period);

        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();

        // Get inventory summary
        $inventorySummary = $this->getInventorySummary($period);

        $reportData = [
            'best_selling_products' => $bestSellingProducts,
            'low_stock_products' => $lowStockProducts,
            'inventory_summary' => $inventorySummary,
            'period' => $period,
            'generated_at' => now()->toDateTimeString()
        ];

        // If email is provided, send the report
        if ($email) {
            $this->sendEmailReport($email, $reportData, $period);
            $this->info("Report sent to {$email}");
        } else if ($adminOnly) {
            // Send to all admin users
            $adminUsers = User::where('type', 'admin')->get();
            foreach ($adminUsers as $user) {
                if ($user->email) {
                    $this->sendEmailReport($user->email, $reportData, $period);
                    $this->info("Report sent to admin: {$user->email}");
                }
            }
        }

        // Save report to database
        $this->saveReport($period, $reportData);

        $this->info('Scheduled reports generated successfully!');

        return 0;
    }

    /**
     * Get best selling products based on period
     */
    private function getBestSellingProducts($period)
    {
        // Calculate date range based on period
        $startDate = $this->getStartDate($period);
        $endDate = now();

        return OrderDetail::join('orders', 'order_details.ord_code', '=', 'orders.id')
            ->join('products', 'order_details.pro_code', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.pro_name as product_name',
                DB::raw('SUM(order_details.qty) as total_quantity'),
                DB::raw('SUM(order_details.amount) as total_revenue')
            )
            ->whereBetween('orders.ord_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.pro_name')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get low stock products
     */
    private function getLowStockProducts()
    {
        return Product::select('id', 'pro_name', 'qty as current_stock')
            ->where('qty', '<=', 10)
            ->where('status', 'active')
            ->orderBy('qty', 'asc')
            ->get();
    }

    /**
     * Get inventory summary
     */
    private function getInventorySummary($period)
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        // Get total products
        $totalProducts = Product::count();

        // Get active products
        $activeProducts = Product::where('status', 'active')->count();

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

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'recent_sales' => [
                'quantity_sold' => $recentSales->total_sold ?? 0,
                'revenue' => $recentSales->total_revenue ?? 0
            ]
        ];
    }

    /**
     * Calculate start date based on period
     */
    private function getStartDate($period)
    {
        switch ($period) {
            case 'weekly':
                return now()->subWeek();
            case 'monthly':
                return now()->subMonth();
            case 'yearly':
                return now()->subYear();
            case 'daily':
            default:
                return now()->subDay();
        }
    }

    /**
     * Send email report
     */
    private function sendEmailReport($email, $reportData, $period)
    {
        try {
            Mail::to($email)->send(new ScheduledReportMail($reportData, $period));
            $this->info("Email sent successfully to: {$email}");
        } catch (\Exception $e) {
            $this->error("Failed to send email to {$email}: " . $e->getMessage());
        }
    }

    /**
     * Save report to database
     */
    private function saveReport($period, $reportData)
    {
        // Save best selling products report
        ScheduledReport::create([
            'report_type' => $period,
            'report_name' => 'best_selling_products',
            'report_data' => $reportData['best_selling_products'],
            'report_period_start' => $this->getStartDate($period),
            'report_period_end' => now(),
            'generated_by' => 'system'
        ]);

        // Save low stock products report
        ScheduledReport::create([
            'report_type' => $period,
            'report_name' => 'low_stock_products',
            'report_data' => $reportData['low_stock_products'],
            'report_period_start' => null,
            'report_period_end' => null,
            'generated_by' => 'system'
        ]);

        // Save inventory summary report
        ScheduledReport::create([
            'report_type' => $period,
            'report_name' => 'inventory_summary',
            'report_data' => $reportData['inventory_summary'],
            'report_period_start' => $this->getStartDate($period),
            'report_period_end' => now(),
            'generated_by' => 'system'
        ]);

        $this->info('Reports saved to database');
    }
}
