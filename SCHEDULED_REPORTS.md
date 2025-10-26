# Scheduled Reports System

## Overview
This system provides automated inventory tracking and reporting capabilities with scheduled reports generation for daily, weekly, monthly, and yearly periods.

## Features
1. Best Selling Products Report
2. Low Stock Products Alert
3. Inventory Summary Report
4. Automated scheduling via Laravel Cron jobs
5. Command-line interface for manual report generation
6. API endpoints for real-time reporting
7. Database storage for historical reports

## API Documentation

For comprehensive API documentation including third-party integration endpoints, please refer to:
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Complete API endpoints and usage

## API Endpoints

### Best Selling Products
```
GET /api/reports/best-selling-products
```
Parameters:
- `period` (optional): daily, weekly, monthly, yearly (default: daily)
- `limit` (optional): Number of products to return (default: 10)

### Low Stock Products
```
GET /api/reports/low-stock-products
```
Parameters:
- `threshold` (optional): Minimum stock threshold (default: 10)

### Inventory Summary
```
GET /api/reports/inventory-summary
```
Parameters:
- `period` (optional): daily, weekly, monthly, yearly (default: daily)

## Command Line Tools

### Generate Scheduled Reports
```
php artisan reports:generate-scheduled [--period=PERIOD] [--email=EMAIL]
```
Parameters:
- `--period`: daily, weekly, monthly, yearly (default: daily)
- `--email`: Email address to send the report to

Examples:
```bash
# Generate daily report
php artisan reports:generate-scheduled --period=daily

# Generate weekly report and send to email
php artisan reports:generate-scheduled --period=weekly --email=admin@example.com
```

## Setting Up Scheduled Tasks

### 1. Configure Laravel Cron Job
Add the following line to your crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Scheduled Tasks Configuration
The following tasks are automatically scheduled:
- Daily reports: Generated at 1:00 AM daily
- Weekly reports: Generated on Mondays at 2:00 AM
- Monthly reports: Generated on the 1st of each month at 3:00 AM

### 3. Customizing Schedule
To modify the schedule, edit `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Daily report generation
    $schedule->command('reports:generate-scheduled --period=daily')
             ->dailyAt('01:00')
             ->description('Generate daily inventory reports');

    // Weekly report generation
    $schedule->command('reports:generate-scheduled --period=weekly')
             ->weeklyOn(1, '2:00') // Monday at 2:00 AM
             ->description('Generate weekly inventory reports');

    // Monthly report generation
    $schedule->command('reports:generate-scheduled --period=monthly')
             ->monthlyOn(1, '3:00') // First day of month at 3:00 AM
             ->description('Generate monthly inventory reports');
}
```

## Database Storage

All generated reports are automatically saved to the `scheduled_reports` table in the database with the following structure:

- `id`: Unique identifier for the report
- `report_type`: Period of the report (daily, weekly, monthly, yearly)
- `report_name`: Type of report (best_selling_products, low_stock_products, inventory_summary)
- `report_data`: JSON data containing the report content
- `report_period_start`: Start date of the reporting period
- `report_period_end`: End date of the reporting period
- `generated_by`: User or system that generated the report
- `created_at`: Timestamp when the report was created
- `updated_at`: Timestamp when the report was last updated

This allows for historical analysis and trend tracking over time.

## Frontend Integration

The scheduled reports are accessible through the web interface:
1. Navigate to "Reports" in the main menu
2. Select "Scheduled Reports"
3. Choose the reporting period (daily, weekly, monthly, yearly)
4. View best selling products and low stock alerts

## Data Structure

### Best Selling Products Response
```json
{
  "success": true,
  "data": [
    {
      "product_id": 1,
      "product_name": "Product Name",
      "total_quantity": 100,
      "total_revenue": 1000.00
    }
  ],
  "period": "daily",
  "start_date": "2023-01-01",
  "end_date": "2023-01-02"
}
```

### Low Stock Products Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "pro_name": "Product Name",
      "current_stock": 5
    }
  ],
  "threshold": 10
}
```

### Inventory Summary Response
```json
{
  "success": true,
  "data": {
    "total_products": 100,
    "active_products": 95,
    "low_stock_products": 5,
    "out_of_stock_products": 2,
    "recent_sales": {
      "quantity_sold": 50,
      "revenue": 500.00
    }
  },
  "period": "daily",
  "start_date": "2023-01-01",
  "end_date": "2023-01-02"
}
```

## Troubleshooting

### Common Issues
1. **Reports not generating**: Ensure the Laravel cron job is properly configured
2. **API returns 401**: Make sure you're authenticated with a valid token
3. **Empty reports**: Verify there is data in the database for the specified period

### Testing
To test the reports system:
```bash
# Test daily report generation
php artisan reports:generate-scheduled --period=daily

# Test API endpoint (requires authentication)
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/reports/best-selling-products
```