<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\Product;

class ImportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Add cache-busting timestamp
        $timestamp = $request->get('_t', time());
        
        $query = Import::with(['staff', 'supplier', 'importDetails.product'])
                       ->withCount('importDetails as details_count');
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhereHas('staff', function($staffQuery) use ($search) {
                      $staffQuery->where('full_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function($supplierQuery) use ($search) {
                      $supplierQuery->where('supplier', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }
        
        if ($request->has('supplier')) {
            $query->where('sup_id', $request->input('supplier'));
        }
        
        if ($request->has('date_from')) {
            $query->where('imp_date', '>=', $request->input('date_from'));
        }
        
        if ($request->has('date_to')) {
            $query->where('imp_date', '<=', $request->input('date_to'));
        }
        
        $imports = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $imports,
            'timestamp' => $timestamp
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imp_date' => 'required|date',
            'staff_id' => 'required|exists:staffs,id',
            'sup_id' => 'required|exists:suppliers,id',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Get staff and supplier info
            $staff = Staff::find($request->staff_id);
            $supplier = Supplier::find($request->sup_id);
            
            // Calculate total from items if provided, otherwise use provided total
            $total = $request->total;
            if ($request->has('items') && is_array($request->items)) {
                $total = 0;
                foreach ($request->items as $item) {
                    $total += $item['qty'] * $item['price'];
                }
            }
            
            // Auto-generate full_name if not provided
            $fullName = $request->full_name ?? "Import from {$supplier->supplier} - " . date('M Y', strtotime($request->imp_date));
            
            // Create import record
            $import = Import::create([
                'imp_date' => $request->imp_date,
                'staff_id' => $request->staff_id,
                'full_name' => $fullName,
                'sup_id' => $request->sup_id,
                'supplier' => $supplier->supplier,
                'total' => $total,
            ]);
            
            // Create import details if items are provided
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    $amount = $item['qty'] * $item['price'];
                    
                    ImportDetail::create([
                        'imp_code' => $import->id,
                        'pro_code' => $item['product_id'],
                        'pro_name' => $product->pro_name,
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'amount' => $amount,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiration_date' => $item['expiration_date'] ?? null,
                    ]);
                    
                    // Update product quantity
                    $product->increment('qty', $item['qty']);
                }
            }
            
            DB::commit();
            
            $import->load(['staff', 'supplier', 'importDetails.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Import created successfully',
                'data' => $import
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $import = Import::with(['staff', 'supplier', 'importDetails.product'])->find($id);
        
        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $import
        ]);
    }

    /**
     * Get import details for a specific import.
     */
    public function getDetails(string $id)
    {
        $import = Import::find($id);
        
        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }

        $details = ImportDetail::with('product')
                              ->where('imp_code', $id)
                              ->get();
        
        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $import = Import::find($id);
        
        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'imp_date' => 'sometimes|date',
            'staff_id' => 'sometimes|exists:staffs,id',
            'sup_id' => 'sometimes|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // If updating items, we need to recalculate everything
            if ($request->has('items')) {
                // Restore original product quantities
                foreach ($import->importDetails as $detail) {
                    $product = Product::find($detail->pro_code);
                    $product->decrement('qty', $detail->qty);
                }
                
                // Delete old details
                ImportDetail::where('imp_code', $import->id)->delete();
                
                // Calculate new total
                $total = 0;
                foreach ($request->items as $item) {
                    $total += $item['qty'] * $item['price'];
                }
                
                // Update import total
                $import->update(['total' => $total]);
                
                // Create new details
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    $amount = $item['qty'] * $item['price'];
                    
                    ImportDetail::create([
                        'imp_code' => $import->id,
                        'pro_code' => $item['product_id'],
                        'pro_name' => $product->pro_name,
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'amount' => $amount,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiration_date' => $item['expiration_date'] ?? null,
                    ]);
                    
                    // Update product quantity
                    $product->increment('qty', $item['qty']);
                }
            }
            
            // Update other fields
            $updateData = [];
            if ($request->has('imp_date')) {
                $updateData['imp_date'] = $request->imp_date;
            }
            if ($request->has('staff_id')) {
                $staff = Staff::find($request->staff_id);
                $updateData['staff_id'] = $request->staff_id;
            }
            if ($request->has('sup_id')) {
                $supplier = Supplier::find($request->sup_id);
                $updateData['sup_id'] = $request->sup_id;
                $updateData['supplier'] = $supplier->supplier;
            }
            
            // Auto-generate full_name when supplier or date changes
            if ($request->has('sup_id') || $request->has('imp_date')) {
                $supplier = $request->has('sup_id') ? Supplier::find($request->sup_id) : $import->supplier;
                $date = $request->has('imp_date') ? $request->imp_date : $import->imp_date;
                $supplierName = is_object($supplier) ? $supplier->supplier : $supplier;
                $updateData['full_name'] = "Import from {$supplierName} - " . date('M Y', strtotime($date));
            }
            
            if (!empty($updateData)) {
                $import->update($updateData);
            }
            
            DB::commit();
            
            $import->load(['staff', 'supplier', 'importDetails.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Import updated successfully',
                'data' => $import
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $import = Import::with('importDetails')->find($id);
        
        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Restore product quantities
            foreach ($import->importDetails as $detail) {
                $product = Product::find($detail->pro_code);
                $product->decrement('qty', $detail->qty);
            }
            
            // Delete import details first
            ImportDetail::where('imp_code', $import->id)->delete();
            
            // Delete import
            $import->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Import deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete import: ' . $e->getMessage()
            ], 500);
        }
    }
}
