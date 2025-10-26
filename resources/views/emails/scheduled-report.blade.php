<!DOCTYPE html>
<html>
<head>
    <title>Scheduled Inventory Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #007bff;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 15px;
        }
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        .summary-value {
            font-size: 1.2em;
            color: #007bff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Management System</h1>
        <h2>Scheduled {{ ucfirst($period) }} Report</h2>
        <p>Generated on: {{ $generatedAt }}</p>
    </div>

    <!-- Inventory Summary -->
    <div class="section">
        <h3 class="section-title">Inventory Summary</h3>
        <div>
            <div class="summary-item">
                <div class="summary-label">Total Products</div>
                <div class="summary-value">{{ $reportData['inventory_summary']['total_products'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Active Products</div>
                <div class="summary-value">{{ $reportData['inventory_summary']['active_products'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Low Stock Items</div>
                <div class="summary-value">{{ $reportData['inventory_summary']['low_stock_products'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Out of Stock</div>
                <div class="summary-value">{{ $reportData['inventory_summary']['out_of_stock_products'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Best Selling Products -->
    <div class="section">
        <h3 class="section-title">Best Selling Products</h3>
        @if(isset($reportData['best_selling_products']) && count($reportData['best_selling_products']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['best_selling_products'] as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->total_quantity }}</td>
                    <td>${{ number_format($product->total_revenue, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No best selling products data available.</p>
        @endif
    </div>

    <!-- Low Stock Products -->
    <div class="section">
        <h3 class="section-title">Low Stock Products</h3>
        @if(isset($reportData['low_stock_products']) && count($reportData['low_stock_products']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Current Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['low_stock_products'] as $product)
                <tr>
                    <td>{{ $product->pro_name }}</td>
                    <td>{{ $product->current_stock }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No low stock products found.</p>
        @endif
    </div>

    <div class="footer">
        <p>This is an automated report from your Inventory Management System.</p>
        <p>Please log in to the system for more detailed information.</p>
    </div>
</body>
</html>