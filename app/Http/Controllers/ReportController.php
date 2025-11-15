<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Import;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ReportController extends Controller
{
    // Import Reports
    public function getImportReport(Request $request)
    {
        // Use the same data processing logic as export methods for consistency
        $imports = $this->getImportData($request);
        
        return response()->json([
            'status' => 'success',
            'data' => $imports
        ]);
    }
    
    public function getImportSummary(Request $request)
    {
        try {
            $query = Import::query();
            
            if ($request->date_from) {
                $query->whereDate('imp_date', '>=', $request->date_from);
            }
            
            if ($request->date_to) {
                $query->whereDate('imp_date', '<=', $request->date_to);
            }
            
            $totalImports = $query->count();
            $totalAmount = $query->sum('total');
            
            \Log::info('Import Summary:', [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'total_imports' => $totalImports,
                'total_amount' => $totalAmount
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_imports' => $totalImports,
                    'total_amount' => floatval($totalAmount ?? 0),
                    'average_amount' => $totalImports > 0 ? floatval($totalAmount) / $totalImports : 0,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Import Summary Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'total_imports' => 0,
                    'total_amount' => 0,
                    'average_amount' => 0,
                ]
            ], 500);
        }
    }
    
    // Sales Reports
    public function getSalesReport(Request $request)
    {
        // Log the request parameters for debugging
        \Log::info('Sales Report Request Parameters:', $request->all());
        
        // Use the same data processing logic as export methods for consistency
        $orders = $this->getSalesData($request);
        
        // Log the result for debugging
        \Log::info('Sales Report Data Count:', ['count' => count($orders)]);
        \Log::info('Sales Report Sample Data:', array_slice($orders, 0, 5));
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
    
    public function getSalesSummary(Request $request)
    {
        try {
            $query = Order::query();
            
            if ($request->date_from) {
                $query->whereDate('ord_date', '>=', $request->date_from);
            }
            
            if ($request->date_to) {
                $query->whereDate('ord_date', '<=', $request->date_to);
            }
            
            $totalOrders = $query->count();
            $totalRevenue = $query->sum('total');
            
            \Log::info('Sales Summary:', [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => floatval($totalRevenue ?? 0),
                    'average_order_value' => $totalOrders > 0 ? floatval($totalRevenue) / $totalOrders : 0,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Sales Summary Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'average_order_value' => 0,
                ]
            ], 500);
        }
    }
    
    // Export Functions
    public function exportImportExcel(Request $request)
    {
        \Log::info('Export Import Excel called with params:', $request->all());
        
        $imports = $this->getImportData($request);
        
        \Log::info('Export Import Excel data count:', ['count' => count($imports)]);
        \Log::info('Export Import Excel sample data:', array_slice($imports, 0, 3));
        
        // Calculate summary statistics
        $totalImports = count(array_unique(array_column($imports, 'id')));
        $totalValue = array_sum(array_column($imports, 'amount'));
        $totalQuantity = array_sum(array_column($imports, 'qty'));
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $sheet->setCellValue('A1', 'Import Report');
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Total Imports: ' . $totalImports);
        $sheet->setCellValue('A4', 'Total Value: $' . number_format($totalValue, 2));
        $sheet->setCellValue('A5', 'Total Quantity: ' . number_format($totalQuantity, 0));
        
        // Table headers - MATCHING PDF TEMPLATE
        $headers = ['ID', 'Date', 'Staff', 'Supplier', 'Product Name', 'Qty', 'Amount', 'Batch Number', 'Expiration Date', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '7', $header);
            $col++;
        }
        
        // Data
        $row = 8;
        foreach ($imports as $import) {
            $sheet->setCellValue('A' . $row, $import['id']);
            $sheet->setCellValue('B' . $row, $import['imp_date']);
            $sheet->setCellValue('C' . $row, $import['staff_name']);
            $sheet->setCellValue('D' . $row, $import['supplier_name']);
            $sheet->setCellValue('E' . $row, $import['product_name']);
            $sheet->setCellValue('F' . $row, $import['qty']);
            $sheet->setCellValue('G' . $row, $import['amount']);
            $sheet->setCellValue('H' . $row, $import['batch_number']);
            $sheet->setCellValue('I' . $row, $import['expiration_date']);
            $sheet->setCellValue('J' . $row, $import['status']);
            $row++;
        }
        
        // Add summary totals at the bottom
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL QUANTITY:');
        $sheet->setCellValue('F' . $row, $totalQuantity);
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL VALUE:');
        $sheet->setCellValue('G' . $row, '$' . number_format($totalValue, 2));
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL IMPORTS:');
        $sheet->setCellValue('F' . $row, $totalImports);
        
        $writer = new Xls($spreadsheet);
        
        $filename = 'import_report_' . now()->format('Y-m-d') . '.xls';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
    
    public function exportImportExcelXlsx(Request $request)
    {
        \Log::info('Export Import Excel XLSX called with params:', $request->all());
        
        $imports = $this->getImportData($request);
        
        \Log::info('Export Import Excel XLSX data count:', ['count' => count($imports)]);
        \Log::info('Export Import Excel XLSX sample data:', array_slice($imports, 0, 3));
        
        // Calculate summary statistics
        $totalImports = count(array_unique(array_column($imports, 'id')));
        $totalValue = array_sum(array_column($imports, 'amount'));
        $totalQuantity = array_sum(array_column($imports, 'qty'));
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $sheet->setCellValue('A1', 'Import Report');
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Total Imports: ' . $totalImports);
        $sheet->setCellValue('A4', 'Total Value: $' . number_format($totalValue, 2));
        $sheet->setCellValue('A5', 'Total Quantity: ' . number_format($totalQuantity, 0));
        
        // Table headers - MATCHING PDF TEMPLATE
        $headers = ['ID', 'Date', 'Staff', 'Supplier', 'Product Name', 'Qty', 'Amount', 'Batch Number', 'Expiration Date', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '7', $header);
            $col++;
        }
        
        // Data
        $row = 8;
        foreach ($imports as $import) {
            $sheet->setCellValue('A' . $row, $import['id']);
            $sheet->setCellValue('B' . $row, $import['imp_date']);
            $sheet->setCellValue('C' . $row, $import['staff_name']);
            $sheet->setCellValue('D' . $row, $import['supplier_name']);
            $sheet->setCellValue('E' . $row, $import['product_name']);
            $sheet->setCellValue('F' . $row, $import['qty']);
            $sheet->setCellValue('G' . $row, $import['amount']);
            $sheet->setCellValue('H' . $row, $import['batch_number']);
            $sheet->setCellValue('I' . $row, $import['expiration_date']);
            $sheet->setCellValue('J' . $row, $import['status']);
            $row++;
        }
        
        // Add summary totals at the bottom
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL QUANTITY:');
        $sheet->setCellValue('F' . $row, $totalQuantity);
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL VALUE:');
        $sheet->setCellValue('G' . $row, '$' . number_format($totalValue, 2));
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL IMPORTS:');
        $sheet->setCellValue('F' . $row, $totalImports);
        
        $writer = new Xlsx($spreadsheet);
        
        $filename = 'import_report_' . now()->format('Y-m-d') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
    
    public function exportImportPdf(Request $request)
    {
        \Log::info('Export Import PDF called with params:', $request->all());
        
        $imports = $this->getImportData($request);
        
        \Log::info('Export Import PDF data count:', ['count' => count($imports)]);
        \Log::info('Export Import PDF sample data:', array_slice($imports, 0, 3));
        
        // Calculate summary statistics
        $totalImports = count(array_unique(array_column($imports, 'id')));
        $totalValue = array_sum(array_column($imports, 'amount'));
        $totalQuantity = array_sum(array_column($imports, 'qty'));
        
        // Ensure proper UTF-8 encoding for PDF
        $imports = array_map(function($import) {
            return array_map(function($value) {
                if (is_string($value)) {
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                return $value;
            }, $import);
        }, $imports);
        
        $pdf = Pdf::loadView('reports.import-pdf', [
            'imports' => $imports,
            'total_records' => count($imports),
            'total_imports' => $totalImports,
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ])->setPaper('a4', 'landscape');
        
        $filename = 'import_report_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    // Sales Export Functions
    public function exportSalesExcel(Request $request)
    {
        \Log::info('Export Sales Excel called with params:', $request->all());
        
        $sales = $this->getSalesData($request);
        
        \Log::info('Export Sales Excel data count:', ['count' => count($sales)]);
        \Log::info('Export Sales Excel sample data:', array_slice($sales, 0, 3));
        
        // Calculate summary statistics
        $totalOrders = count(array_unique(array_column($sales, 'id')));
        $totalSales = array_sum(array_column($sales, 'amount'));
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $sheet->setCellValue('A1', 'Sales Report');
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Total Orders: ' . $totalOrders);
        $sheet->setCellValue('A4', 'Total Sales: $' . number_format($totalSales, 2));
        
        // Table headers - MATCHING PDF TEMPLATE
        $headers = ['ID', 'Date', 'Customer', 'Staff', 'Product Name', 'Qty', 'Amount', 'Payment Status', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $col++;
        }
        
        // Data
        $row = 7;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $sale['id']);
            $sheet->setCellValue('B' . $row, $sale['ord_date']);
            $sheet->setCellValue('C' . $row, $sale['cus_name']);
            $sheet->setCellValue('D' . $row, $sale['staff_name']);
            $sheet->setCellValue('E' . $row, $sale['product_name']);
            $sheet->setCellValue('F' . $row, $sale['qty']);
            $sheet->setCellValue('G' . $row, $sale['amount']);
            $sheet->setCellValue('H' . $row, $sale['payment_status']);
            $sheet->setCellValue('I' . $row, $sale['status']);
            $row++;
        }
        
        // Add summary totals at the bottom
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL ORDERS:');
        $sheet->setCellValue('F' . $row, $totalOrders);
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL SALES:');
        $sheet->setCellValue('G' . $row, '$' . number_format($totalSales, 2));
        
        $writer = new Xls($spreadsheet);
        
        $filename = 'sales_report_' . now()->format('Y-m-d') . '.xls';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    public function exportSalesExcelXlsx(Request $request)
    {
        \Log::info('Export Sales Excel XLSX called with params:', $request->all());
        
        $sales = $this->getSalesData($request);
        
        \Log::info('Export Sales Excel XLSX data count:', ['count' => count($sales)]);
        \Log::info('Export Sales Excel XLSX sample data:', array_slice($sales, 0, 3));
        
        // Calculate summary statistics
        $totalOrders = count(array_unique(array_column($sales, 'id')));
        $totalSales = array_sum(array_column($sales, 'amount'));
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $sheet->setCellValue('A1', 'Sales Report');
        $sheet->setCellValue('A2', 'Generated on: ' . now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Total Orders: ' . $totalOrders);
        $sheet->setCellValue('A4', 'Total Sales: $' . number_format($totalSales, 2));
        
        // Table headers - MATCHING PDF TEMPLATE
        $headers = ['ID', 'Date', 'Customer', 'Staff', 'Product Name', 'Qty', 'Amount', 'Payment Status', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $col++;
        }
        
        // Data
        $row = 7;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $sale['id']);
            $sheet->setCellValue('B' . $row, $sale['ord_date']);
            $sheet->setCellValue('C' . $row, $sale['cus_name']);
            $sheet->setCellValue('D' . $row, $sale['staff_name']);
            $sheet->setCellValue('E' . $row, $sale['product_name']);
            $sheet->setCellValue('F' . $row, $sale['qty']);
            $sheet->setCellValue('G' . $row, $sale['amount']);
            $sheet->setCellValue('H' . $row, $sale['payment_status']);
            $sheet->setCellValue('I' . $row, $sale['status']);
            $row++;
        }
        
        // Add summary totals at the bottom
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL ORDERS:');
        $sheet->setCellValue('F' . $row, $totalOrders);
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL SALES:');
        $sheet->setCellValue('G' . $row, '$' . number_format($totalSales, 2));
        
        $writer = new Xlsx($spreadsheet);
        
        $filename = 'sales_report_' . now()->format('Y-m-d') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    public function exportSalesPdf(Request $request)
    {
        \Log::info('Export Sales PDF called with params:', $request->all());
        
        $sales = $this->getSalesData($request);
        
        \Log::info('Export Sales PDF data count:', ['count' => count($sales)]);
        \Log::info('Export Sales PDF sample data:', array_slice($sales, 0, 3));
        
        // Calculate summary statistics
        $totalOrders = count(array_unique(array_column($sales, 'id')));
        $totalSales = array_sum(array_column($sales, 'amount'));
        
        // Ensure proper UTF-8 encoding for PDF
        $sales = array_map(function($sale) {
            return array_map(function($value) {
                if (is_string($value)) {
                    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                return $value;
            }, $sale);
        }, $sales);
        
        $pdf = Pdf::loadView('reports.sales-pdf', [
            'sales' => $sales,
            'total_records' => count($sales),
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ])->setPaper('a4', 'landscape');
        
        $filename = 'sales_report_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    // Helper Methods
    private function getImportData(Request $request)
    {
        $query = Import::with(['supplier', 'staff', 'importDetails.product']);
        
        if ($request->date_from) {
            // Parse date more robustly
            try {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('imp_date', '>=', $dateFrom);
            } catch (\Exception $e) {
                \Log::warning('Invalid date_from format:', ['date_from' => $request->date_from, 'error' => $e->getMessage()]);
            }
        }
        
        if ($request->date_to) {
            // Parse date more robustly
            try {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('imp_date', '<=', $dateTo);
            } catch (\Exception $e) {
                \Log::warning('Invalid date_to format:', ['date_to' => $request->date_to, 'error' => $e->getMessage()]);
            }
        }
        
        if ($request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }
        
        if ($request->supplier_id) {
            $query->where('sup_id', $request->supplier_id);
        }
        
        $imports = $query->orderBy('imp_date', 'desc')->get();
        
        // Flatten the data to one row per product
        $flattenedData = [];
        foreach ($imports as $import) {
            foreach ($import->importDetails as $detail) {
                // Try to get product name from multiple sources
                $productName = 'Unknown Product';
                if (!empty($detail->pro_name)) {
                    $productName = $detail->pro_name;
                } elseif (!empty($detail->product) && !empty($detail->product->pro_name)) {
                    $productName = $detail->product->pro_name;
                } elseif (!empty($detail->product_name)) {
                    $productName = $detail->product_name;
                }
                
                // Calculate quantity and amount properly
                $qty = $detail->qty ?? 0;
                $amount = $detail->amount ?? 0;
                
                // If amount is 0 but we have qty and price, calculate it
                if ($amount == 0 && $qty > 0 && !empty($detail->price)) {
                    $amount = $qty * $detail->price;
                }
                
                // Get batch number with fallback
                $batchNumber = 'N/A';
                if (!empty($detail->batch_number)) {
                    $batchNumber = $detail->batch_number;
                }
                
                // Get expiration date with fallback
                $expirationDate = 'N/A';
                if (!empty($detail->expiration_date)) {
                    $expirationDate = $detail->expiration_date->format('Y-m-d');
                }
                
                // Get supplier name with multiple fallbacks
                $supplierName = 'Unknown Supplier';
                if (!empty($import->supplier_name)) {
                    $supplierName = $import->supplier_name;
                } elseif (!empty($import->supplier) && !empty($import->supplier->supplier)) {
                    $supplierName = $import->supplier->supplier;
                } elseif (!empty($import->supplier)) {
                    $supplierName = $import->supplier;
                }
                
                // Get staff name with multiple fallbacks
                $staffName = 'Unknown Staff';
                if (!empty($import->staff_name)) {
                    $staffName = $import->staff_name;
                } elseif (!empty($import->staff) && !empty($import->staff->full_name)) {
                    $staffName = $import->staff->full_name;
                } elseif (!empty($import->full_name)) {
                    $staffName = $import->full_name;
                } elseif (!empty($import->staff)) {
                    $staffName = $import->staff;
                }
                
                $flattenedData[] = [
                    'id' => $import->id,
                    'imp_date' => $import->imp_date ? $import->imp_date->format('Y-m-d') : 'N/A',
                    'staff_name' => $staffName,
                    'supplier_name' => $supplierName,
                    'product_name' => $productName,
                    'qty' => $qty,
                    'amount' => $amount,
                    'batch_number' => $batchNumber,
                    'expiration_date' => $expirationDate,
                    'status' => $import->status ?? 'completed',
                ];
            }
        }
        
        return $flattenedData;
    }

    private function getSalesData(Request $request)
    {
        \Log::info('getSalesData called with params:', $request->all());
        
        $query = Order::with(['customer', 'staff', 'orderDetails.product', 'payments']);
        
        // Log the raw query for debugging
        \Log::info('Base query built');
        
        if ($request->date_from) {
            \Log::info('Applying date_from filter:', ['date_from' => $request->date_from]);
            // Parse date more robustly
            try {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('ord_date', '>=', $dateFrom);
            } catch (\Exception $e) {
                \Log::warning('Invalid date_from format:', ['date_from' => $request->date_from, 'error' => $e->getMessage()]);
            }
        }
        
        if ($request->date_to) {
            \Log::info('Applying date_to filter:', ['date_to' => $request->date_to]);
            // Parse date more robustly
            try {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('ord_date', '<=', $dateTo);
            } catch (\Exception $e) {
                \Log::warning('Invalid date_to format:', ['date_to' => $request->date_to, 'error' => $e->getMessage()]);
            }
        }
        
        if ($request->staff_id) {
            \Log::info('Applying staff_id filter:', ['staff_id' => $request->staff_id]);
            $query->where('staff_id', $request->staff_id);
        }
        
        if ($request->customer_id) {
            \Log::info('Applying customer_id filter:', ['customer_id' => $request->customer_id]);
            $query->where('cus_id', $request->customer_id);
        }
        
        $orders = $query->orderBy('ord_date', 'desc')->get();
        
        \Log::info('Orders fetched:', ['count' => $orders->count()]);
        
        // Flatten the data to one row per product
        $flattenedData = [];
        foreach ($orders as $order) {
            \Log::info('Processing order:', ['order_id' => $order->id, 'order_details_count' => $order->orderDetails->count(), 'payments_count' => $order->payments->count()]);
            
            // Calculate payment information for this order
            $totalPaid = 0;
            $paymentStatus = 'Unpaid';
            
            // Safely calculate total paid
            if ($order->payments && $order->payments->count() > 0) {
                $totalPaid = $order->payments->sum('deposit');
                \Log::info('Payment data found:', ['total_paid' => $totalPaid, 'order_total' => $order->total]);
            }
            
            // Determine payment status
            if ($order->total && $totalPaid >= $order->total) {
                $paymentStatus = 'Paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'Partial';
            }
            
            \Log::info('Payment status determined:', ['status' => $paymentStatus]);
            
            // Process each order detail
            if ($order->orderDetails && $order->orderDetails->count() > 0) {
                foreach ($order->orderDetails as $detail) {
                    \Log::info('Processing order detail:', ['detail_id' => $detail->ord_code . '-' . $detail->pro_code]);
                    
                    // Try to get product name from multiple sources
                    $productName = 'Unknown Product';
                    if (!empty($detail->pro_name)) {
                        $productName = $detail->pro_name;
                    } elseif (!empty($detail->product) && !empty($detail->product->pro_name)) {
                        $productName = $detail->product->pro_name;
                    } elseif (!empty($detail->product_name)) {
                        $productName = $detail->product_name;
                    }
                    
                    // Get quantity with fallback
                    $qty = 0;
                    if (isset($detail->qty) && !is_null($detail->qty)) {
                        $qty = $detail->qty;
                    }
                    
                    // Get amount with fallback
                    $amount = 0;
                    if (isset($detail->amount) && !is_null($detail->amount)) {
                        $amount = $detail->amount;
                    } elseif (isset($detail->price) && isset($detail->qty)) {
                        // Calculate amount from price and quantity if amount is not set
                        $amount = $detail->price * $detail->qty;
                    }
                    
                    // Get customer name with fallbacks
                    $customerName = 'Unknown Customer';
                    if (!empty($order->cus_name)) {
                        $customerName = $order->cus_name;
                    } elseif (!empty($order->customer) && !empty($order->customer->name)) {
                        $customerName = $order->customer->name;
                    }
                    
                    // Get staff name with fallbacks
                    $staffName = 'Unknown Staff';
                    if (!empty($order->full_name)) {
                        $staffName = $order->full_name;
                    } elseif (!empty($order->staff) && !empty($order->staff->full_name)) {
                        $staffName = $order->staff->full_name;
                    }
                    
                    $flattenedData[] = [
                        'id' => $order->id ?? 0,
                        'ord_date' => $order->ord_date ? $order->ord_date->format('Y-m-d') : 'N/A',
                        'cus_name' => $customerName,
                        'staff_name' => $staffName,
                        'product_name' => $productName,
                        'qty' => $qty,
                        'amount' => $amount,
                        'payment_status' => $paymentStatus,
                        'status' => $order->status ?? 'completed',
                    ];
                }
            } else {
                // If there are no order details, still show the order with default values
                $customerName = 'Unknown Customer';
                if (!empty($order->cus_name)) {
                    $customerName = $order->cus_name;
                } elseif (!empty($order->customer) && !empty($order->customer->name)) {
                    $customerName = $order->customer->name;
                }
                
                // Get staff name with fallbacks
                $staffName = 'Unknown Staff';
                if (!empty($order->full_name)) {
                    $staffName = $order->full_name;
                } elseif (!empty($order->staff) && !empty($order->staff->full_name)) {
                    $staffName = $order->staff->full_name;
                }
                
                $flattenedData[] = [
                    'id' => $order->id ?? 0,
                    'ord_date' => $order->ord_date ? $order->ord_date->format('Y-m-d') : 'N/A',
                    'cus_name' => $customerName,
                    'staff_name' => $staffName,
                    'product_name' => 'No Products',
                    'qty' => 0,
                    'amount' => 0,
                    'payment_status' => $paymentStatus,
                    'status' => $order->status ?? 'completed',
                ];
            }
        }
        
        \Log::info('Final flattened data count:', ['count' => count($flattenedData)]);
        
        return $flattenedData;
    }
}