<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Staff;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController extends Controller
{
    /**
     * Get import report with filters
     */
    public function importReport(Request $request)
    {
        $query = Import::with(['staff', 'supplier', 'importDetails.product'])
            ->join('import_details', 'imports.id', '=', 'import_details.imp_code')
            ->join('staffs', 'imports.staff_id', '=', 'staffs.id')
            ->join('suppliers', 'imports.sup_id', '=', 'suppliers.id')
            ->join('products', 'import_details.pro_code', '=', 'products.id')
            ->select(
                'imports.id as import_id',
                'imports.imp_date',
                'staffs.full_name as staff_name',
                'suppliers.supplier as supplier_name',
                'products.pro_name as product_name',
                'import_details.qty',
                'import_details.price as unit_price',
                'import_details.amount',
                'imports.total as import_total',
                'products.batch_number as batch_number',
                'products.expiration_date as expiration_date'
            );

        // Apply filters
        if ($request->has('staff_id')) {
            $query->where('imports.staff_id', $request->staff_id);
        }
        if ($request->has('supplier_id')) {
            $query->where('imports.sup_id', $request->supplier_id);
        }
        if ($request->has('product_id')) {
            $query->where('import_details.pro_code', $request->product_id);
        }
        if ($request->has('date_from')) {
            $query->where('imports.imp_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('imports.imp_date', '<=', $request->date_to);
        }

        $reports = $query->orderBy('imports.imp_date', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Get sales report with filters
     */
    public function salesReport(Request $request)
    {
        $query = Order::with(['staff', 'customer', 'orderDetails.product'])
            ->join('order_details', 'orders.id', '=', 'order_details.ord_code')
            ->join('staffs', 'orders.staff_id', '=', 'staffs.id')
            ->join('customers', 'orders.cus_id', '=', 'customers.id')
            ->join('products', 'order_details.pro_code', '=', 'products.id')
            ->select(
                'orders.id as order_id',
                'orders.ord_date',
                'staffs.full_name as staff_name',
                'customers.cus_name as customer_name',
                'products.pro_name as product_name',
                'order_details.qty',
                'order_details.price as unit_price',
                'order_details.amount',
                'orders.total as order_total'
            );

        // Apply filters
        if ($request->has('staff_id')) {
            $query->where('orders.staff_id', $request->staff_id);
        }
        if ($request->has('customer_id')) {
            $query->where('orders.cus_id', $request->customer_id);
        }
        if ($request->has('product_id')) {
            $query->where('order_details.pro_code', $request->product_id);
        }
        if ($request->has('date_from')) {
            $query->where('orders.ord_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('orders.ord_date', '<=', $request->date_to);
        }

        $reports = $query->orderBy('orders.ord_date', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Get import summary
     */
    public function importSummary(Request $request)
    {
        $query = ImportDetail::join('imports', 'import_details.imp_code', '=', 'imports.id');

        if ($request->has('date_from')) {
            $query->where('imports.imp_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('imports.imp_date', '<=', $request->date_to);
        }

        $summary = $query->selectRaw('
            COUNT(DISTINCT imports.id) as total_imports,
            SUM(import_details.qty) as total_qty,
            SUM(import_details.amount) as total_amount
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get sales summary
     */
    public function salesSummary(Request $request)
    {
        $query = OrderDetail::join('orders', 'order_details.ord_code', '=', 'orders.id');

        if ($request->has('date_from')) {
            $query->where('orders.ord_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('orders.ord_date', '<=', $request->date_to);
        }

        $summary = $query->selectRaw('
            COUNT(DISTINCT orders.id) as total_orders,
            SUM(order_details.qty) as total_qty,
            SUM(order_details.amount) as total_amount
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Export import report to Excel (CSV format)
     */
    public function exportImportExcel(Request $request)
    {
        $data = $this->getImportReportData($request);

        $filename = 'import_report_' . date('Y_m_d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            // Imported Goods Management Report
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Import ID', 'Date', 'Staff', 'Supplier', 'Product', 'Qty', 'Unit Price', 'Amount', 'Total', 'Batch Number', 'Expiration Date']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->import_id,
                    $row->imp_date,
                    $row->staff_name,
                    $row->supplier_name,
                    $row->product_name,
                    $row->qty,
                    $row->unit_price,
                    $row->amount,
                    $row->import_total,
                    $row->batch_number ?? 'N/A',
                    $row->expiration_date ? date('Y-m-d', strtotime($row->expiration_date)) : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * Export import report to Excel (XLSX format)
     */
    public function exportImportExcelXlsx(Request $request)
    {
        try {
            $data = $this->getImportReportData($request);
            $summary = $this->getImportSummaryData($request);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator("Imported Goods Management Report")
                ->setTitle("Import Report")
                ->setSubject("Import Report Data");

            // Add header
            $sheet->setCellValue('A1', 'Imported Goods Management Report');
            $sheet->setCellValue('A2', 'Import Report');
            $sheet->setCellValue('A3', 'Generated on: ' . date('Y-m-d'));

            // Add summary data
            $sheet->setCellValue('A5', 'Summary:');
            $sheet->setCellValue('A6', 'Total Imports:');
            $sheet->setCellValue('B6', $summary->total_imports ?? 0);
            $sheet->setCellValue('A7', 'Total Quantity:');
            $sheet->setCellValue('B7', $summary->total_qty ?? 0);
            $sheet->setCellValue('A8', 'Total Amount:');
            $sheet->setCellValue('B8', '$' . number_format($summary->total_amount ?? 0, 2));

            // Add table headers
            $row = 20;
            $sheet->setCellValue('A' . $row, 'Import ID');
            $sheet->setCellValue('B' . $row, 'Date');
            $sheet->setCellValue('C' . $row, 'Staff');
            $sheet->setCellValue('D' . $row, 'Supplier');
            $sheet->setCellValue('E' . $row, 'Product');
            $sheet->setCellValue('F' . $row, 'Qty');
            $sheet->setCellValue('G' . $row, 'Unit Price');
            $sheet->setCellValue('H' . $row, 'Amount');
            $sheet->setCellValue('I' . $row, 'Batch Number');
            $sheet->setCellValue('J' . $row, 'Expiration Date');

            // Style the header row
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD3D3D3');

            // Add data rows
            $row++;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item->import_id);
                $sheet->setCellValue('B' . $row, $item->imp_date);
                $sheet->setCellValue('C' . $row, $item->staff_name);
                $sheet->setCellValue('D' . $row, $item->supplier_name);
                $sheet->setCellValue('E' . $row, $item->product_name);
                $sheet->setCellValue('F' . $row, $item->qty);
                $sheet->setCellValue('G' . $row, $item->unit_price);
                $sheet->setCellValue('H' . $row, $item->amount);
                $sheet->setCellValue('I' . $row, $item->batch_number ?? 'N/A');
                $sheet->setCellValue('J' . $row, $item->expiration_date ? date('Y-m-d', strtotime($item->expiration_date)) : 'N/A');
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Set number formats
            $sheet->getStyle('G2:H' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

            $filename = 'import_report_' . date('Y_m_d') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Excel report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export sales report to Excel (CSV format)
     */
    public function exportSalesExcel(Request $request)
    {
        $data = $this->getSalesReportData($request);

        $filename = 'sales_report_' . date('Y_m_d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Date', 'Staff', 'Customer', 'Product', 'Qty', 'Unit Price', 'Amount', 'Total']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->order_id,
                    $row->ord_date,
                    $row->staff_name,
                    $row->customer_name,
                    $row->product_name,
                    $row->qty,
                    $row->unit_price,
                    $row->amount,
                    $row->order_total
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export sales report to Excel (XLSX format)
     */
    public function exportSalesExcelXlsx(Request $request)
    {
        try {
            $data = $this->getSalesReportData($request);
            $summary = $this->getSalesSummaryData($request);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator("Stock Sales Management Report")
                ->setTitle("Sales Report")
                ->setSubject("Sales Report Data");

            // Add header
            $sheet->setCellValue('A1', 'Stock Sales Management Report');
            $sheet->setCellValue('A2', 'Sales Report');
            $sheet->setCellValue('A3', 'Generated on: ' . date('Y-m-d'));

            // Add summary data
            $sheet->setCellValue('A5', 'Summary:');
            $sheet->setCellValue('A6', 'Total Orders:');
            $sheet->setCellValue('B6', $summary->total_orders ?? 0);
            $sheet->setCellValue('A7', 'Total Quantity:');
            $sheet->setCellValue('B7', $summary->total_qty ?? 0);
            $sheet->setCellValue('A8', 'Total Amount:');
            $sheet->setCellValue('B8', '$' . number_format($summary->total_amount ?? 0, 2));

            // Add table headers
            $row = 10;
            $sheet->setCellValue('A' . $row, 'Order ID');
            $sheet->setCellValue('B' . $row, 'Date');
            $sheet->setCellValue('C' . $row, 'Staff');
            $sheet->setCellValue('D' . $row, 'Customer');
            $sheet->setCellValue('E' . $row, 'Product');
            $sheet->setCellValue('F' . $row, 'Qty');
            $sheet->setCellValue('G' . $row, 'Unit Price');
            $sheet->setCellValue('H' . $row, 'Amount');

            // Style the header row
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD3D3D3');

            // Add data rows
            $row++;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item->order_id);
                $sheet->setCellValue('B' . $row, $item->ord_date);
                $sheet->setCellValue('C' . $row, $item->staff_name);
                $sheet->setCellValue('D' . $row, $item->customer_name);
                $sheet->setCellValue('E' . $row, $item->product_name);
                $sheet->setCellValue('F' . $row, $item->qty);
                $sheet->setCellValue('G' . $row, $item->unit_price);
                $sheet->setCellValue('H' . $row, $item->amount);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Set number formats
            $sheet->getStyle('G2:H' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

            $filename = 'sales_report_' . date('Y_m_d') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Excel report: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getImportReportData($request)
    {
        $query = DB::table('imports')
            ->join('import_details', 'imports.id', '=', 'import_details.imp_code')
            ->join('staffs', 'imports.staff_id', '=', 'staffs.id')
            ->join('suppliers', 'imports.sup_id', '=', 'suppliers.id')
            ->join('products', 'import_details.pro_code', '=', 'products.id')
            ->select(
                'imports.id as import_id',
                'imports.imp_date',
                'staffs.full_name as staff_name',
                'suppliers.supplier as supplier_name',
                'products.pro_name as product_name',
                'import_details.qty',
                'import_details.price as unit_price',
                'import_details.amount',
                'imports.total as import_total',
                'products.batch_number as batch_number',
                'products.expiration_date as expiration_date'
            );

        // Apply same filters as report
        if ($request->has('staff_id')) {
            $query->where('imports.staff_id', $request->staff_id);
        }
        if ($request->has('supplier_id')) {
            $query->where('imports.sup_id', $request->supplier_id);
        }
        if ($request->has('product_id')) {
            $query->where('import_details.pro_code', $request->product_id);
        }
        if ($request->has('date_from')) {
            $query->where('imports.imp_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('imports.imp_date', '<=', $request->date_to);
        }

        return $query->orderBy('imports.imp_date', 'desc')->get();
    }

    private function getSalesReportData($request)
    {
        $query = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.ord_code')
            ->join('staffs', 'orders.staff_id', '=', 'staffs.id')
            ->join('customers', 'orders.cus_id', '=', 'customers.id')
            ->join('products', 'order_details.pro_code', '=', 'products.id')
            ->select(
                'orders.id as order_id',
                'orders.ord_date',
                'staffs.full_name as staff_name',
                'customers.cus_name as customer_name',
                'products.pro_name as product_name',
                'order_details.qty',
                'order_details.price as unit_price',
                'order_details.amount',
                'orders.total as order_total'
            );

        // Apply same filters as report
        if ($request->has('staff_id')) {
            $query->where('orders.staff_id', $request->staff_id);
        }
        if ($request->has('customer_id')) {
            $query->where('orders.cus_id', $request->customer_id);
        }
        if ($request->has('product_id')) {
            $query->where('order_details.pro_code', $request->product_id);
        }
        if ($request->has('date_from')) {
            $query->where('orders.ord_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('orders.ord_date', '<=', $request->date_to);
        }

        return $query->orderBy('orders.ord_date', 'desc')->get();
    }


    /**
     * Export import report to PDF
     */
    public function exportImportPdf(Request $request)
    {
        try {
            $data = $this->getImportReportData($request);
            $summary = $this->getImportSummaryData($request);

            if (!$data->count() && !$summary) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data available for the selected filters.'
                ], 400);
            }

            // Ensure proper UTF-8 encoding for Khmer text
            $data = $data->map(function ($item) {
                $item->staff_name = mb_convert_encoding($item->staff_name ?? '', 'UTF-8', 'UTF-8');
                $item->supplier_name = mb_convert_encoding($item->supplier_name ?? '', 'UTF-8', 'UTF-8');
                $item->product_name = mb_convert_encoding($item->product_name ?? '', 'UTF-8', 'UTF-8');
                return $item;
            });

            $summary->total_imports = mb_convert_encoding($summary->total_imports ?? 0, 'UTF-8', 'UTF-8');
            $summary->total_qty = mb_convert_encoding($summary->total_qty ?? 0, 'UTF-8', 'UTF-8');
            $summary->total_amount = mb_convert_encoding($summary->total_amount ?? 0, 'UTF-8', 'UTF-8');

            $html = $this->generateImportPdfHtml($data, $summary, $request);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions(['defaultFont' => 'DejaVu Sans']);

            $filename = 'import_report_' . date('Y_m_d') . '.pdf';
            return $pdf->download($filename);  // Ensure this returns the file directly
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export sales report to PDF
     */
    public function exportSalesPdf(Request $request)
    {
        try {
            $data = $this->getSalesReportData($request);
            $summary = $this->getSalesSummaryData($request);

            // Ensure proper UTF-8 encoding for Khmer text
            $data = $data->map(function ($item) {
                $item->staff_name = mb_convert_encoding($item->staff_name ?? '', 'UTF-8', 'UTF-8');
                $item->customer_name = mb_convert_encoding($item->customer_name ?? '', 'UTF-8', 'UTF-8');
                $item->product_name = mb_convert_encoding($item->product_name ?? '', 'UTF-8', 'UTF-8');
                return $item;
            });

            $summary->total_orders = mb_convert_encoding($summary->total_orders ?? 0, 'UTF-8', 'UTF-8');
            $summary->total_qty = mb_convert_encoding($summary->total_qty ?? 0, 'UTF-8', 'UTF-8');
            $summary->total_amount = mb_convert_encoding($summary->total_amount ?? 0, 'UTF-8', 'UTF-8');

            $html = $this->generateSalesPdfHtml($data, $summary, $request);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions(['defaultFont' => 'DejaVu Sans']);

            $filename = 'sales_report_' . date('Y_m_d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export single import record to Word
     */
    public function exportSingleImportWord(Request $request)
    {
        try {
            $importId = $request->get('import_id');
            if (!$importId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import ID is required'
                ], 400);
            }

            // Get single import record data
            $data = DB::table('imports')
                ->join('import_details', 'imports.id', '=', 'import_details.imp_code')
                ->join('staffs', 'imports.staff_id', '=', 'staffs.id')
                ->join('suppliers', 'imports.sup_id', '=', 'suppliers.id')
                ->join('products', 'import_details.pro_code', '=', 'products.id')
                ->select(
                    'imports.id as import_id',
                    'imports.imp_date',
                    'staffs.full_name as staff_name',
                    'suppliers.supplier as supplier_name',
                    'products.pro_name as product_name',
                    'import_details.qty',
                    'import_details.price as unit_price',
                    'import_details.amount',
                    'imports.total as import_total'
                )
                ->where('imports.id', $importId)
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import record not found'
                ], 404);
            }

            $filename = $this->generateImportWordDocument($data, 'import');

            return response()->download($filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Word document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export single sales record to Word
     */
    public function exportSingleSalesWord(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required'
                ], 400);
            }

            // Get single sales record data
            $data = DB::table('orders')
                ->join('order_details', 'orders.id', '=', 'order_details.ord_code')
                ->join('staffs', 'orders.staff_id', '=', 'staffs.id')
                ->join('customers', 'orders.cus_id', '=', 'customers.id')
                ->join('products', 'order_details.pro_code', '=', 'products.id')
                ->select(
                    'orders.id as order_id',
                    'orders.ord_date',
                    'staffs.full_name as staff_name',
                    'customers.cus_name as customer_name',
                    'products.pro_name as product_name',
                    'order_details.qty',
                    'order_details.price as unit_price',
                    'order_details.amount',
                    'orders.total as order_total'
                )
                ->where('orders.id', $orderId)
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales record not found'
                ], 404);
            }

            $filename = $this->generateImportWordDocument($data, 'sales');

            return response()->download($filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Word document: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getImportSummaryData($request)
    {
        $query = ImportDetail::join('imports', 'import_details.imp_code', '=', 'imports.id');

        if ($request->has('date_from')) {
            $query->where('imports.imp_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('imports.imp_date', '<=', $request->date_to);
        }
        if ($request->has('staff_id')) {
            $query->where('imports.staff_id', $request->staff_id);
        }
        if ($request->has('supplier_id')) {
            $query->where('imports.sup_id', $request->supplier_id);
        }
        if ($request->has('product_id')) {
            $query->where('import_details.pro_code', $request->product_id);
        }

        return $query->selectRaw('
            COUNT(DISTINCT imports.id) as total_imports,
            SUM(import_details.qty) as total_qty,
            SUM(import_details.amount) as total_amount
        ')->first();
    }

    private function getSalesSummaryData($request)
    {
        $query = OrderDetail::join('orders', 'order_details.ord_code', '=', 'orders.id');

        if ($request->has('date_from')) {
            $query->where('orders.ord_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('orders.ord_date', '<=', $request->date_to);
        }
        if ($request->has('staff_id')) {
            $query->where('orders.staff_id', $request->staff_id);
        }
        if ($request->has('customer_id')) {
            $query->where('orders.cus_id', $request->customer_id);
        }
        if ($request->has('product_id')) {
            $query->where('order_details.pro_code', $request->product_id);
        }

        return $query->selectRaw('
            COUNT(DISTINCT orders.id) as total_orders,
            SUM(order_details.qty) as total_qty,
            SUM(order_details.amount) as total_amount
        ')->first();
    }

    private function generateImportPdfHtml($data, $summary, $request)
    {
        $dateRange = '';
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateRange = 'From ' . $request->date_from . ' to ' . $request->date_to;
        } elseif ($request->has('date_from')) {
            $dateRange = 'From ' . $request->date_from;
        } elseif ($request->has('date_to')) {
            $dateRange = 'To ' . $request->date_to;
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="utf-8">
        <title>Import Report</title>
            <style>
                @font-face {
                    font-family: "DejaVu Sans";
                    src: url("http://localhost:8000/fonts/DejaVuSans.ttf") format("truetype");
                }
                @font-face {
                    font-family: "DejaVu Sans";
                    src: url("http://localhost:8000/fonts/DejaVuSans-Bold.ttf") format("truetype");
                    font-weight: bold;
                }
                body { 
                    font-family: "DejaVu Sans", sans-serif; 
                    font-size: 12px; 
                }
                .header { text-align: center; margin-bottom: 30px; }
                .company { font-size: 18px; font-weight: bold; color: #333; }
                .title { font-size: 16px; font-weight: bold; margin: 10px 0; }
                .info { margin: 5px 0; color: #666; }
                .summary { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .summary-item { display: inline-block; margin-right: 30px; }
                .summary-label { font-weight: bold; color: #333; }
                .summary-value { color: #007bff; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .amount { text-align: right; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 10px; }
                .no-data { text-align: center; color: #888; padding: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company"></div>
                <div class="title">Stock Import Management Report</div>
                <div class="info">Created at: ' . date('Y-m-d') . '</div>
                ' . ($dateRange ? '<div class="info">' . $dateRange . '</div>' : '') . '
            </div>
            
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Total Imports:</div>
                    <div class="summary-value">' . number_format($summary->total_imports ?? 0) . '</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Quantity:</div>
                    <div class="summary-value">' . number_format($summary->total_qty ?? 0) . '</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Amount:</div>
                    <div class="summary-value">$' . number_format($summary->total_amount ?? 0, 2) . '</div>
                </div>
            </div>

            ' . ($data->isEmpty() ? '<div class="no-data">No data available for the selected filters.</div>' : '
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Staffs</th>
                        <th>Supplier</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Batch Number</th>
                        <th>Expiration Date</th>
                    </tr>
                </thead>
                <tbody>'
            . $data->map(function ($row) {
                return '
                        <tr>
                            <td>' . $row->import_id . '</td>
                            <td>' . $row->imp_date . '</td>
                            <td>' . htmlspecialchars($row->staff_name ?? '') . '</td>
                            <td>' . htmlspecialchars($row->supplier_name ?? '') . '</td>
                            <td>' . htmlspecialchars($row->product_name ?? '') . '</td>
                            <td>' . number_format($row->qty ?? 0) . '</td>
                            <td class="amount">$' . number_format($row->unit_price ?? 0, 2) . '</td>
                            <td class="amount">$' . number_format($row->amount ?? 0, 2) . '</td>
                            <td>' . htmlspecialchars($row->batch_number ?? 'N/A') . '</td>
                            <td>' . ($row->expiration_date ? date('Y-m-d', strtotime($row->expiration_date)) : 'N/A') . '</td>
                        </tr>';
            })->join('') . '
                </tbody>
            </table>') . '
            
            <div class="footer">
                <p>Stock Import Management Report</p>
            </div>
        </body>
        </html>';

        return $html;
    }


    private function generateSalesPdfHtml($data, $summary, $request)
    {
        $dateRange = '';
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateRange = 'From ' . $request->date_from . ' to ' . $request->date_to;
        } elseif ($request->has('date_from')) {
            $dateRange = 'From ' . $request->date_from;
        } elseif ($request->has('date_to')) {
            $dateRange = 'To ' . $request->date_to;
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Sales Report</title>
            <style>
                @font-face {
                    font-family: "DejaVu Sans";
                    src: url("http://localhost:8000/fonts/DejaVuSans.ttf") format("truetype");
                }
                @font-face {
                    font-family: "DejaVu Sans";
                    src: url("http://localhost:8000/fonts/DejaVuSans-Bold.ttf") format("truetype");
                    font-weight: bold;
                }
                body { 
                    font-family: "DejaVu Sans", sans-serif; 
                    font-size: 12px; 
                }
                .header { text-align: center; margin-bottom: 30px; }
                .company { font-size: 18px; font-weight: bold; color: #333; }
                .title { font-size: 16px; font-weight: bold; margin: 10px 0; }
                .info { margin: 5px 0; color: #666; }
                .summary { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .summary-item { display: inline-block; margin-right: 30px; }
                .summary-label { font-weight: bold; color: #333; }
                .summary-value { color: #007bff; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .amount { text-align: right; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 10px; }
                .no-data { text-align: center; color: #888; padding: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company">Stock Sales Management Report</div>
                <div class="title">Sale Report</div>
                <div class="info">Created at: ' . date('Y-m-d') . '</div>
                ' . ($dateRange ? '<div class="info">' . $dateRange . '</div>' : '') . '
            </div>
            

            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Total Orders:</div>
                    <div class="summary-value">' . number_format($summary->total_orders ?? 0) . '</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Quantity:</div>
                    <div class="summary-value">' . number_format($summary->total_qty ?? 0) . '</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Amount:</div>
                    <div class="summary-value">$' . number_format($summary->total_amount ?? 0, 2) . '</div>
                </div>
            </div>

            ' . ($data->isEmpty() ? '<div class="no-data">No data available for the selected filters.</div>' : '
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Staffs</th>
                        <th>Suppliers</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>'
            . $data->map(function ($row) {
                return '
                        <tr>
                            <td>' . $row->order_id . '</td>
                            <td>' . $row->ord_date . '</td>
                            <td>' . htmlspecialchars($row->staff_name ?? '') . '</td>
                            <td>' . htmlspecialchars($row->customer_name ?? '') . '</td>
                            <td>' . htmlspecialchars($row->product_name ?? '') . '</td>
                            <td>' . number_format($row->qty ?? 0) . '</td>
                            <td class="amount">$' . number_format($row->unit_price ?? 0, 2) . '</td>
                            <td class="amount">$' . number_format($row->amount ?? 0, 2) . '</td>
                        </tr>';
            })->join('') . '
                </tbody>
            </table>') . '
            
            <div class="footer">
                <p>Stock Sales Management Report</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Generate Word document for single record
     */
    private function generateImportWordDocument($data, $type)
    {
        $phpWord = new PhpWord();

        // Add section
        $section = $phpWord->addSection([
            'marginLeft' => 600,
            'marginRight' => 600,
            'marginTop' => 600,
            'marginBottom' => 600,
        ]);

        // Title Style
        $titleStyle = ['name' => 'Arial', 'size' => 18, 'bold' => true, 'color' => '000080'];
        $headerStyle = ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '333333'];
        $labelStyle = ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => '666666'];
        $valueStyle = ['name' => 'Arial', 'size' => 11, 'color' => '000000'];
        $centerAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        // Header
        $section->addText('Imported Goods Management Report', $titleStyle, $centerAlign);
        $section->addText(strtoupper($type) . ' REPORT', $headerStyle, $centerAlign);
        $section->addText('Generated on: ' . date('F d, Y \a\t'), $valueStyle, $centerAlign);
        $section->addTextBreak(2);

        // Transaction Information Header
        $section->addText('TRANSACTION INFORMATION', $headerStyle);
        $section->addTextBreak(1);

        // Create table for transaction details
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'cccccc',
            'cellMargin' => 80
        ]);

        // Transaction ID
        $table->addRow();
        $table->addCell(3000)->addText('Transaction ID:', $labelStyle);
        $idValue = $type === 'import' ? $data->import_id : $data->order_id;
        $table->addCell(6000)->addText('#' . $idValue, $valueStyle);

        // Date
        $table->addRow();
        $table->addCell(3000)->addText('Date:', $labelStyle);
        $dateValue = $type === 'import' ? $data->imp_date : $data->ord_date;
        $table->addCell(6000)->addText(date('F d, Y', strtotime($dateValue)), $valueStyle);

        // Staff
        $table->addRow();
        $table->addCell(3000)->addText('Staff:', $labelStyle);
        $table->addCell(6000)->addText($data->staff_name, $valueStyle);

        // Supplier/Customer
        $table->addRow();
        $thirdPartyLabel = $type === 'import' ? 'Supplier:' : 'Customer:';
        $thirdPartyValue = $type === 'import' ? $data->supplier_name : $data->customer_name;
        $table->addCell(3000)->addText($thirdPartyLabel, $labelStyle);
        $table->addCell(6000)->addText($thirdPartyValue, $valueStyle);

        $section->addTextBreak(2);

        // Product Information Header
        $section->addText('PRODUCT DETAILS', $headerStyle);
        $section->addTextBreak(1);

        // Product details table
        $productTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'cccccc',
            'cellMargin' => 80
        ]);

        // Table headers
        $productTable->addRow();
        $productTable->addCell(4000)->addText('Product Name', $labelStyle);
        $productTable->addCell(2000)->addText('Quantity', $labelStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $productTable->addCell(3000)->addText('Unit Price', $labelStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $productTable->addCell(3000)->addText('Amount', $labelStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

        // Product details
        $productTable->addRow();
        $productTable->addCell(4000)->addText($data->product_name, $valueStyle);
        $productTable->addCell(2000)->addText(number_format($data->qty), $valueStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $productTable->addCell(3000)->addText('$' . number_format($data->unit_price, 2), $valueStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $productTable->addCell(3000)->addText('$' . number_format($data->amount, 2), $valueStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

        $section->addTextBreak(2);

        // Total
        $section->addText(
            'TOTAL: $' . number_format($data->import_total ?? $data->order_total, 2),
            array_merge($headerStyle, ['size' => 16]),
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );

        $section->addTextBreak(3);

        // Footer
        $section->addText(
            'Imported Goods Management Report',
            ['name' => 'Arial', 'size' => 9, 'color' => '666666'],
            $centerAlign
        );
        $section->addText(
            'Confidential - For Internal Use Only',
            ['name' => 'Arial', 'size' => 8, 'color' => '999999'],
            $centerAlign
        );

        // Save file
        $filename = $type . '_report_' . date('Y_m_d_H_i') . '.docx';
        $tempFile = storage_path('app/public/' . $filename);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }
}
