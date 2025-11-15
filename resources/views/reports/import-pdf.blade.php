<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Import Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4CAF50;
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

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .total {
            font-weight: bold;
            background-color: #e8f5e9;
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

        .status-expired {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Import Report</h1>
    </div>

    <div class="info">
        <p><strong>Generated on:</strong> {{ $generated_at }}</p>
        <p><strong>Total Imports:</strong> {{ $total_imports ?? count(array_unique(array_column($imports, 'id'))) }}</p>
        <p><strong>Total Value:</strong> ${{ number_format($total_value ?? array_sum(array_column($imports, 'amount')), 2) }}</p>
        <p><strong>Total Quantity:</strong> {{ number_format($total_quantity ?? array_sum(array_column($imports, 'qty')), 0) }}</p>
        <p><strong>Date Range:</strong>
            @if ($date_from && $date_to)
                {{ $date_from }} to {{ $date_to }}
            @else
                All dates
            @endif
        </p>
        <p><strong>Data Source:</strong> Real Database</p>
    </div>

    @if (count($imports) > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Staff</th>
                    <th>Supplier</th>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Batch Number</th>
                    <th>Expiration Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($imports as $import)
                    <tr>
                        <td>{{ $import['id'] }}</td>
                        <td>{{ $import['imp_date'] }}</td>
                        <td>{{ $import['staff_name'] }}</td>
                        <td>{{ $import['supplier_name'] }}</td>
                        <td>{{ $import['product_name'] }}</td>
                        <td>{{ $import['qty'] }}</td>
                        <td>${{ number_format($import['amount'], 2) }}</td>
                        <td>{{ $import['batch_number'] ?? '-' }}</td>
                        <td>{{ $import['expiration_date'] ?? '-' }}</td>
                        <td>{{ ucfirst($import['status']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="info total">
            <p><strong>Total Quantity:</strong> {{ number_format($total_quantity ?? array_sum(array_column($imports, 'qty')), 0) }}</p>
            <p><strong>Total Value:</strong> ${{ number_format($total_value ?? array_sum(array_column($imports, 'amount')), 2) }}</p>
            <p><strong>Total Imports:</strong> {{ $total_imports ?? count(array_unique(array_column($imports, 'id'))) }}</p>
        </div>
    @else
        <div class="no-data">
            <h3>No Import Data Available</h3>
            <p>No import records found for the selected date range.</p>
            <p>Please check your date range or ensure there are import records in the database.</p>
        </div>
    @endif
</body>

</html>
