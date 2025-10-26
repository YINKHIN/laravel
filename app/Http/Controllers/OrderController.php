<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Payment;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Add cache-busting timestamp
        $timestamp = $request->get('_t', time());
        
        $query = Order::with(['staff', 'customer', 'orderDetails.product', 'payments']);
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('cus_name', 'like', "%{$search}%")
                  ->orWhereHas('staff', function($staffQuery) use ($search) {
                      $staffQuery->where('full_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('cus_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }
        
        if ($request->has('customer_id')) {
            $query->where('cus_id', $request->input('customer_id'));
        }
        
        if ($request->has('date_from')) {
            $query->where('ord_date', '>=', $request->input('date_from'));
        }
        
        if ($request->has('date_to')) {
            $query->where('ord_date', '<=', $request->input('date_to'));
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $orders,
            'timestamp' => $timestamp
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ord_date' => 'required|date',
            'staff_id' => 'required|exists:staffs,id',
            'cus_id' => 'nullable|exists:customers,id',  // Made nullable
            'cus_name' => 'nullable|string|max:255',  // Accept customer name as text
            'total' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'discount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
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
            // Check product availability
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->qty < $item['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->pro_name}. Available: {$product->qty}, Requested: {$item['qty']}"
                    ], 422);
                }
            }
            
            // Get staff info
            $staff = Staff::find($request->staff_id);
            
            // Get customer info if provided
            $customer = null;
            $customerName = null;
            if ($request->cus_id) {
                $customer = Customer::find($request->cus_id);
                $customerName = $customer->cus_name;
            } else if ($request->cus_name) {
                $customerName = $request->cus_name;
            }
            
            // Create order record
            $order = Order::create([
                'ord_date' => $request->ord_date,
                'staff_id' => $request->staff_id,
                'full_name' => $staff->full_name,
                'cus_id' => $request->cus_id,  // Can be null
                'cus_name' => $customerName,  // Store customer name directly
                'total' => $request->total ?? 0,
                'subtotal' => $request->subtotal ?? 0,
                'tax' => $request->tax ?? 0,
                'tax_percent' => $request->tax_percent ?? 0,
                'discount' => $request->discount ?? 0,
                'discount_percent' => $request->discount_percent ?? 0,
            ]);
            
            // Create order details and update product quantities
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $amount = $item['qty'] * $item['price'];
                
                OrderDetail::create([
                    'ord_code' => $order->id,
                    'pro_code' => $item['product_id'],
                    'pro_name' => $product->pro_name,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'amount' => $amount,
                ]);
                
                // Decrease product quantity
                $product->decrement('qty', $item['qty']);
            }
            
            DB::commit();
            
            $order->load(['staff', 'customer', 'orderDetails.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['staff', 'customer', 'orderDetails.product', 'payments'])->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'ord_date' => 'sometimes|date',
            'staff_id' => 'sometimes|exists:staffs,id',
            'cus_id' => 'sometimes|exists:customers,id',
            'cus_name' => 'nullable|string|max:255',  // Accept customer name as text
            'total' => 'sometimes|numeric|min:0',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'discount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.qty' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
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
                // Check product availability (restore original quantities first)
                foreach ($order->orderDetails as $detail) {
                    $product = Product::find($detail->pro_code);
                    $product->increment('qty', $detail->qty);
                }
                
                // Check new availability
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->qty < $item['qty']) {
                        // Rollback the increment we just did
                        foreach ($order->orderDetails as $detail) {
                            $product = Product::find($detail->pro_code);
                            $product->decrement('qty', $detail->qty);
                        }
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for product: {$product->pro_name}. Available: {$product->qty}, Requested: {$item['qty']}"
                        ], 422);
                    }
                }
                
                // Delete old details
                OrderDetail::where('ord_code', $order->id)->delete();
                
                // Create new details
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    $amount = $item['qty'] * $item['price'];
                    
                    OrderDetail::create([
                        'ord_code' => $order->id,
                        'pro_code' => $item['product_id'],
                        'pro_name' => $product->pro_name,
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'amount' => $amount,
                    ]);
                    
                    // Decrease product quantity
                    $product->decrement('qty', $item['qty']);
                }
            }
            
            // Update other fields
            $updateData = [];
            if ($request->has('ord_date')) {
                $updateData['ord_date'] = $request->ord_date;
            }
            if ($request->has('staff_id')) {
                $staff = Staff::find($request->staff_id);
                $updateData['staff_id'] = $request->staff_id;
                $updateData['full_name'] = $staff->full_name;
            }
            if ($request->has('cus_id')) {
                $customer = Customer::find($request->cus_id);
                $updateData['cus_id'] = $request->cus_id;
                $updateData['cus_name'] = $customer->cus_name;
            } else if ($request->has('cus_name')) {
                $updateData['cus_name'] = $request->cus_name;
            }
            if ($request->has('total')) {
                $updateData['total'] = $request->total;
            }
            if ($request->has('subtotal')) {
                $updateData['subtotal'] = $request->subtotal;
            }
            if ($request->has('tax')) {
                $updateData['tax'] = $request->tax;
            }
            if ($request->has('tax_percent')) {
                $updateData['tax_percent'] = $request->tax_percent;
            }
            if ($request->has('discount')) {
                $updateData['discount'] = $request->discount;
            }
            if ($request->has('discount_percent')) {
                $updateData['discount_percent'] = $request->discount_percent;
            }
            
            if (!empty($updateData)) {
                $order->update($updateData);
            }
            
            DB::commit();
            
            $order->load(['staff', 'customer', 'orderDetails.product']);
            
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::with(['orderDetails', 'payments'])->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            $paymentsCount = $order->payments->count();
            
            // Delete all associated payments first
            if ($paymentsCount > 0) {
                Payment::where('ord_code', $order->id)->delete();
            }
            
            // Restore product quantities
            foreach ($order->orderDetails as $detail) {
                $product = Product::find($detail->pro_code);
                if ($product) {
                    $product->increment('qty', $detail->qty);
                }
            }
            
            // Delete order details
            OrderDetail::where('ord_code', $order->id)->delete();
            
            // Delete order
            $order->delete();
            
            DB::commit();
            
            $message = $paymentsCount > 0 
                ? "Order and {$paymentsCount} associated payment(s) deleted successfully"
                : 'Order deleted successfully';
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Force delete order with all related payments (Admin only)
     */
    public function forceDestroy(string $id)
    {
        $order = Order::with(['orderDetails', 'payments'])->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Delete all payments first
            Payment::where('ord_code', $order->id)->delete();
            
            // Restore product quantities
            foreach ($order->orderDetails as $detail) {
                $product = Product::find($detail->pro_code);
                if ($product) {
                    $product->increment('qty', $detail->qty);
                }
            }
            
            // Delete order details
            OrderDetail::where('ord_code', $order->id)->delete();
            
            // Delete order
            $order->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order and all related payments deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to force delete order: ' . $e->getMessage()
            ], 500);
        }
    }
}