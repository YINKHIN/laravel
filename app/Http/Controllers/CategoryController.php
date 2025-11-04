<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            if ($request->has('per_page')) {
                $perPage = min($request->per_page, 100); // Max 100 items per page
                $categories = $query->paginate($perPage);
            } else {
                $categories = $query->get();
            }

            return response()->json([
                'success' => true,
                'data' => $categories instanceof \Illuminate\Pagination\LengthAwarePaginator 
                    ? $categories->items() 
                    : $categories,
                'pagination' => $categories instanceof \Illuminate\Pagination\LengthAwarePaginator 
                    ? [
                        'current_page' => $categories->currentPage(),
                        'per_page' => $categories->perPage(),
                        'total' => $categories->total(),
                        'last_page' => $categories->lastPage(),
                        'from' => $categories->firstItem(),
                        'to' => $categories->lastItem()
                    ] 
                    : null,
                'total' => $categories instanceof \Illuminate\Pagination\LengthAwarePaginator 
                    ? $categories->total() 
                    : $categories->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Category index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }

    /**
     * Get only active categories.
     */
    public function active(Request $request)
    {
        try {
            $query = Category::active();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $categories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => $categories->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Category active error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active categories'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:100|unique:categories,name',
                'description' => 'nullable|string|max:500',
                'status' => 'sometimes|in:active,inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120' // 5MB max
            ];
            
            $validatedData = $request->validate($rules);

            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                
                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Store image
                $imageUrl = $image->storeAs('categories', $filename, 'public');
            }

            $category = Category::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'image' => $imageUrl,
                'status' => $validatedData['status'] ?? 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully!',
                'data' => $category->fresh() // Refresh to get image_url
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Category store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Category show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            $rules = [
                'name' => 'required|string|max:100|unique:categories,name,' . $id,
                'description' => 'nullable|string|max:500',
                'status' => 'sometimes|in:active,inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120', // 5MB max
                'remove_image' => 'sometimes|boolean'
            ];
            
            $validatedData = $request->validate($rules);

            // Handle image upload/removal
            $imageUrl = $category->image; // Keep existing image by default
            
            if ($request->hasFile('image')) {
                // New image uploaded - delete old one if exists
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                
                $image = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imageUrl = $image->storeAs('categories', $filename, 'public');
                
            } else if ($request->has('remove_image') && $request->remove_image) {
                // Remove image flag set - delete existing image
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                $imageUrl = null;
            }

            $category->update([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? $category->description,
                'image' => $imageUrl,
                'status' => $validatedData['status'] ?? $category->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'data' => $category->fresh() // Refresh to get updated image_url
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Category update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image for category.
     */
    public function uploadImage(Request $request, string $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120' // 5MB max
            ]);

            // Delete old image if exists
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            // Store new image
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imageUrl = $image->storeAs('categories', $filename, 'public');

            $category->update(['image' => $imageUrl]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully!',
                'data' => [
                    'image' => $imageUrl,
                    'image_url' => $category->fresh()->image_url
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Category image upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove image from category.
     */
    public function removeImage(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $category->update(['image' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully!',
                'data' => $category->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Category image removal error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check if category has products
            $productCount = $category->products()->count();
            $forceDelete = $request->query('force', false);
            
            if ($productCount > 0 && !$forceDelete) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category with {$productCount} existing products. Please remove or reassign those products first.",
                    'product_count' => $productCount,
                    'can_force_delete' => true
                ], 422);
            }
            
            // If force delete, remove all products in this category first
            if ($forceDelete && $productCount > 0) {
                $category->products()->delete();
            }
            
            // Delete image if exists
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
            
            $categoryName = $category->name;
            $category->delete();
            
            $message = $forceDelete && $productCount > 0 
                ? "Category '{$categoryName}' and {$productCount} associated products deleted successfully!"
                : "Category '{$categoryName}' deleted successfully!";
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Category destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }
}