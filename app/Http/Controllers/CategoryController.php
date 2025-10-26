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

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $categories = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => $categories->count()
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive'
            ];
            
            // Only add image validation if a file is being uploaded
            if ($request->hasFile('image')) {
                $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            } else {
                $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            }
            
            $validatedData = $request->validate($rules);

            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageUrl = $request->file('image')->store('categories', 'public');
            }

            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $imageUrl,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully!',
                'data' => $category
            ], 201);
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
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive'
            ];
            
            // Only add image validation if a file is being uploaded
            if ($request->hasFile('image')) {
                $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            } else {
                $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            }
            
            $validatedData = $request->validate($rules);

            // Handle image upload
            $imageUrl = $category->image; // Keep existing image by default
            
            if ($request->hasFile('image')) {
                // New image uploaded - delete old one if exists
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $imageUrl = $request->file('image')->store('categories', 'public');
            } else if ($request->has('delete_image') && $request->delete_image) {
                // Delete image flag set - remove existing image
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $imageUrl = null;
            }

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $imageUrl,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Category update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing products. Please remove those products first.'
                ], 422);
            }
            
            // Delete image if exists
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            
            $category->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Category destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }
}