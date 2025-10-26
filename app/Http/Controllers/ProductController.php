<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pro_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Add filter for low stock items
        if ($request->has('low_stock')) {
            $query->whereColumn('qty', '<=', 'reorder_point');
        }

        // Add filter for expired products
        if ($request->has('expired')) {
            $query->where('expiration_date', '<', now());
        }

        // Add filter for near expiration products
        if ($request->has('near_expiration')) {
            $query->where('expiration_date', '>=', now())
                  ->where('expiration_date', '<=', now()->addDays(30));
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'pro_name' => 'required|string|max:255',
            'pro_description' => 'nullable|string',
            'upis' => 'required|numeric|min:0',
            'sup' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:1',
            'batch_number' => 'nullable|string|max:50',
            'expiration_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ];

        $request->validate($rules);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'pro_name' => $request->pro_name,
            'pro_description' => $request->pro_description,
            'upis' => $request->upis,
            'sup' => $request->sup,
            'qty' => $request->qty,
            'image' => $imageUrl,
            'status' => $request->status ?? 'active',
            'reorder_point' => $request->reorder_point ,
            'reorder_quantity' => $request->reorder_quantity,
            'batch_number' => $request->batch_number,
            'expiration_date' => $request->expiration_date
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully!',
            'data' => $product->load(['category', 'brand'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with(['category', 'brand'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'pro_name' => 'required|string|max:255',
            'pro_description' => 'nullable|string',
            'upis' => 'required|numeric|min:0',
            'sup' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:1',
            'batch_number' => 'nullable|string|max:50',
            'expiration_date' => 'nullable|date'
        ];

        // Only add image validation if a file is being uploaded
        if ($request->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        } else {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }

        $request->validate($rules);

        // Handle image
        $imageUrl = $product->image; // Keep existing image by default

        if ($request->hasFile('image')) {
            // New image uploaded - delete old one if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imageUrl = $request->file('image')->store('products', 'public');
        } else if ($request->has('delete_image') && $request->delete_image) {
            // Delete image flag set - remove existing image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imageUrl = null;
        }

        $product->update([
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'pro_name' => $request->pro_name,
            'pro_description' => $request->pro_description,
            'upis' => $request->upis,
            'sup' => $request->sup,
            'qty' => $request->qty,
            'image' => $imageUrl,
            'status' => $request->status ?? $product->status,
            'reorder_point' => $request->reorder_point ?? $product->reorder_point,
            'reorder_quantity' => $request->reorder_quantity ?? $product->reorder_quantity,
            'batch_number' => $request->batch_number,
            'expiration_date' => $request->expiration_date
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully!',
            'data' => $product->load(['category', 'brand'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Check if product has related records that prevent deletion
        $orderDetailsCount = \DB::table('order_details')->where('pro_code', $id)->count();
        $importDetailsCount = \DB::table('import_details')->where('pro_code', $id)->count();

        if ($orderDetailsCount > 0 || $importDetailsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product because it has transaction history. Consider marking it as inactive instead.',
                'details' => [
                    'order_details' => $orderDetailsCount,
                    'import_details' => $importDetailsCount
                ]
            ], 422);
        }

        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully!'
        ]);
    }

    /**
     * Mark product as inactive instead of deleting
     */
    public function deactivate(string $id)
    {
        $product = Product::findOrFail($id);
        
        $product->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Product marked as inactive successfully!',
            'data' => $product->load(['category', 'brand'])
        ]);
    }

    /**
     * Mark product as active
     */
    public function activate(string $id)
    {
        $product = Product::findOrFail($id);
        
        $product->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Product activated successfully!',
            'data' => $product->load(['category', 'brand'])
        ]);
    }

    /**
     * Get products that need reordering
     */
    public function getLowStockProducts(Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->whereColumn('qty', '<=', 'reorder_point');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pro_name', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get expired products
     */
    public function getExpiredProducts(Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->where('expiration_date', '<', now());

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pro_name', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get products near expiration
     */
    public function getNearExpirationProducts(Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->where('expiration_date', '>=', now())
            ->where('expiration_date', '<=', now()->addDays(30));

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pro_name', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}