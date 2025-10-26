<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('supplier', 'like', "%{$search}%")
                  ->orWhere('sup_add', 'like', "%{$search}%")
                  ->orWhere('sup_con', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $suppliers = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier' => 'required|string|max:100|unique:suppliers',
            'sup_add' => 'required|string|max:100',
            'sup_con' => 'nullable|string|max:100|unique:suppliers',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Supplier::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $supplier = Supplier::find($id);
        
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::find($id);
        
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'supplier' => 'sometimes|string|max:100|unique:suppliers,supplier,' . $id,
            'sup_add' => 'sometimes|string|max:100',
            'sup_con' => 'nullable|string|max:100|unique:suppliers,sup_con,' . $id,
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::find($id);
        
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
        
        // Check if supplier has related imports
        if ($supplier->imports()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with existing imports'
            ], 422);
        }
        
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ]);
    }
}
