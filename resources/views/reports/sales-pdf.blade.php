<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        .info {
            background: #f5f5f5;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
            background-color: #e3f2fd;
            text-align: right;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .status-completed {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report</h1>
    </div>
    
    <div class="info">
        <p><strong>Generated on:</strong> {{ $generated_at }}</p>
        <p><strong>Total Orders:</strong> {{ $total_orders ?? count(array_unique(array_column($sales, 'id'))) }}</p>
        <p><strong>Total Sales:</strong> ${{ number_format($total_sales ?? array_sum(array_column($sales, 'amount')), 2) }}</p>
        <p><strong>Date Range:</strong> 
            @if($date_from && $date_to)
                {{ $date_from }} to {{ $date_to }}
            @else
                All dates
            @endif
        </p>
        <p><strong>Data Source:</strong> Real Database</p>
    </div>
    
    @if(count($sales) > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Staff</th>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale['id'] }}</td>
                    <td>{{ $sale['ord_date'] }}</td>
                    <td>{{ $sale['cus_name'] }}</td>
                    <td>{{ $sale['staff_name'] }}</td>
                    <td>{{ $sale['product_name'] }}</td>
                    <td>{{ $sale['qty'] }}</td>
                    <td>${{ number_format($sale['amount'], 2) }}</td>
                    <td>{{ $sale['payment_status'] }}</td>
                    <td>{{ ucfirst($sale['status']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="info total">
            <p><strong>Total Orders:</strong> {{ $total_orders ?? count(array_unique(array_column($sales, 'id'))) }}</p>
            <p><strong>Total Sales:</strong> ${{ number_format($total_sales ?? array_sum(array_column($sales, 'amount')), 2) }}</p>
        </div>
    @else
        <div class="no-data">
            <h3>No Sales Data Available</h3>
            <p>No sales records found for the selected date range.</p>
            <p>Please check your date range or ensure there are sales records in the database.</p>
        </div>
    @endif
</body>
</html>