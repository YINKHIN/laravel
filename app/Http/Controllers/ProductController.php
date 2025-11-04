<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Test public method
     */
    public function testPublic()
    {
        return response()->json([
            'success' => true,
            'message' => 'ProductController public method works',
            'timestamp' => now()
        ]);
    }

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048'
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
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048';
        } else {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048';
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

    /**
     * Get featured products for e-commerce
     */
    public function getFeaturedProducts(Request $request)
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => 4.5, // Mock rating
                    'reviews' => rand(10, 100), // Mock reviews
                    'featured' => true
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get best selling products for e-commerce
     */
    public function getBestSellers(Request $request)
    {
        $limit = $request->get('limit', 8);
        
        // Mock best sellers based on random selection for now
        // In a real app, you'd calculate this based on order_details
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => rand(40, 50) / 10, // Mock rating 4.0-5.0
                    'reviews' => rand(50, 200), // Mock reviews
                    'bestSeller' => true,
                    'soldCount' => rand(100, 500) // Mock sold count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get new arrival products for e-commerce
     */
    public function getNewArrivals(Request $request)
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => rand(35, 50) / 10, // Mock rating 3.5-5.0
                    'reviews' => rand(5, 50), // Mock reviews
                    'isNew' => true,
                    'createdAt' => $product->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get products on sale for e-commerce
     */
    public function getOnSaleProducts(Request $request)
    {
        $limit = $request->get('limit', 8);
        
        // Products where selling price is less than original price
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->whereColumn('sup', '<', 'upis')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                $discount = round((($product->upis - $product->sup) / $product->upis) * 100);
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => rand(35, 50) / 10, // Mock rating 3.5-5.0
                    'reviews' => rand(10, 100), // Mock reviews
                    'onSale' => true,
                    'discount' => $discount
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Search products for e-commerce
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $limit = $request->get('limit', 20);
        
        $productsQuery = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0);

        if ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('pro_name', 'like', "%{$query}%")
                  ->orWhere('pro_description', 'like', "%{$query}%");
            });
        }

        if ($category) {
            $productsQuery->whereHas('category', function ($q) use ($category) {
                $q->where('name', 'like', "%{$category}%");
            });
        }

        if ($minPrice) {
            $productsQuery->where('sup', '>=', $minPrice);
        }

        if ($maxPrice) {
            $productsQuery->where('sup', '<=', $maxPrice);
        }

        $products = $productsQuery->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => rand(30, 50) / 10, // Mock rating 3.0-5.0
                    'reviews' => rand(5, 150) // Mock reviews
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
            'query' => $query,
            'total' => $products->count()
        ]);
    }

    /**
     * Get products by category for e-commerce
     */
    public function getByCategory(Request $request, $categoryId)
    {
        $limit = $request->get('limit', 20);
        
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->where('category_id', $categoryId)
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->pro_name,
                    'description' => $product->pro_description,
                    'price' => $product->sup,
                    'originalPrice' => $product->upis,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'brand' => $product->brand ? $product->brand->name : null,
                    'inStock' => $product->qty > 0,
                    'quantity' => $product->qty,
                    'rating' => rand(30, 50) / 10, // Mock rating 3.0-5.0
                    'reviews' => rand(5, 150) // Mock reviews
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
            'category_id' => $categoryId
        ]);
    }
}